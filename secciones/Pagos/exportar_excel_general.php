<?php
// actualizar_balance.php

function actualizarBalanceApto($id_apto, $id_condominio)
{
   global $conexion;

   try {
      // 1. CALCULAR DEUDA TOTAL (suma de todos los tickets PENDIENTES)
      $sentencia_tickets = $conexion->prepare("
            SELECT SUM(mantenimiento + mora + gas + cuota) as total_deuda 
            FROM tbl_tickets 
            WHERE id_apto = :id_apto 
            AND id_condominio = :id_condominio 
            AND estado = 'Pendiente'
        ");
      $sentencia_tickets->bindParam(":id_apto", $id_apto);
      $sentencia_tickets->bindParam(":id_condominio", $id_condominio);
      $sentencia_tickets->execute();
      $deuda = $sentencia_tickets->fetch(PDO::FETCH_ASSOC)['total_deuda'] ?? 0;

      // 2. CALCULAR TOTAL PAGADO (suma de todos los pagos)
      $sentencia_pagos = $conexion->prepare("
            SELECT SUM(monto) as total_pagos 
            FROM tbl_pagos 
            WHERE id_apto = :id_apto 
            AND id_condominio = :id_condominio
        ");
      $sentencia_pagos->bindParam(":id_apto", $id_apto);
      $sentencia_pagos->bindParam(":id_condominio", $id_condominio);
      $sentencia_pagos->execute();
      $pagos = $sentencia_pagos->fetch(PDO::FETCH_ASSOC)['total_pagos'] ?? 0;

      // 3. CALCULAR BALANCE = PAGOS - DEUDA
      $balance = $pagos - $deuda;

      // 4. ACTUALIZAR FECHA DEL ÚLTIMO PAGO (si hay pagos)
      if ($pagos > 0) {
         $sentencia_ultimo_pago = $conexion->prepare("
                SELECT MAX(fecha_pago) as ultima_fecha 
                FROM tbl_pagos 
                WHERE id_apto = :id_apto 
                AND id_condominio = :id_condominio
            ");
         $sentencia_ultimo_pago->bindParam(":id_apto", $id_apto);
         $sentencia_ultimo_pago->bindParam(":id_condominio", $id_condominio);
         $sentencia_ultimo_pago->execute();
         $ultima_fecha = $sentencia_ultimo_pago->fetch(PDO::FETCH_ASSOC)['ultima_fecha'];

         // Actualizar fecha_ultimo_pago en tbl_aptos
         $sentencia_fecha = $conexion->prepare("
                UPDATE tbl_aptos 
                SET fecha_ultimo_pago = :fecha_ultimo_pago 
                WHERE id = :id_apto 
                AND id_condominio = :id_condominio
            ");
         $sentencia_fecha->bindParam(":fecha_ultimo_pago", $ultima_fecha);
         $sentencia_fecha->bindParam(":id_apto", $id_apto);
         $sentencia_fecha->bindParam(":id_condominio", $id_condominio);
         $sentencia_fecha->execute();
      }

      // 5. ACTUALIZAR BALANCE EN tbl_aptos
      $sentencia_actualizar = $conexion->prepare("
            UPDATE tbl_aptos 
            SET balance = :balance 
            WHERE id = :id_apto 
            AND id_condominio = :id_condominio
        ");
      $sentencia_actualizar->bindParam(":balance", $balance);
      $sentencia_actualizar->bindParam(":id_apto", $id_apto);
      $sentencia_actualizar->bindParam(":id_condominio", $id_condominio);
      $sentencia_actualizar->execute();

      return [
         'success' => true,
         'balance' => $balance,
         'deuda' => $deuda,
         'pagos' => $pagos,
         'formula' => "$pagos - $deuda = $balance"
      ];
   } catch (Exception $e) {
      return [
         'success' => false,
         'error' => $e->getMessage()
      ];
   }
}

// Función para debug - ver cálculo detallado
function debugBalanceApto($id_apto, $id_condominio)
{
   global $conexion;

   // Obtener tickets pendientes
   $sentencia_tickets = $conexion->prepare("
        SELECT mes, anio, mantenimiento, mora, gas, cuota, 
               (mantenimiento + mora + gas + cuota) as total_ticket
        FROM tbl_tickets 
        WHERE id_apto = :id_apto 
        AND id_condominio = :id_condominio 
        AND estado = 'Pendiente'
        ORDER BY anio, FIELD(mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')
    ");
   $sentencia_tickets->bindParam(":id_apto", $id_apto);
   $sentencia_tickets->bindParam(":id_condominio", $id_condominio);
   $sentencia_tickets->execute();
   $tickets = $sentencia_tickets->fetchAll(PDO::FETCH_ASSOC);

   // Obtener pagos
   $sentencia_pagos = $conexion->prepare("
        SELECT concepto, monto, fecha_pago 
        FROM tbl_pagos 
        WHERE id_apto = :id_apto 
        AND id_condominio = :id_condominio
        ORDER BY fecha_pago DESC
    ");
   $sentencia_pagos->bindParam(":id_apto", $id_apto);
   $sentencia_pagos->bindParam(":id_condominio", $id_condominio);
   $sentencia_pagos->execute();
   $pagos = $sentencia_pagos->fetchAll(PDO::FETCH_ASSOC);

   return [
      'tickets_pendientes' => $tickets,
      'pagos' => $pagos,
      'total_deuda' => array_sum(array_column($tickets, 'total_ticket')),
      'total_pagos' => array_sum(array_column($pagos, 'monto'))
   ];
}

// Función para actualizar todos los apartamentos
function actualizarTodosBalances($id_condominio)
{
   global $conexion;

   try {
      // Obtener todos los apartamentos
      $sentencia_aptos = $conexion->prepare("
            SELECT id, apto FROM tbl_aptos 
            WHERE id_condominio = :id_condominio
        ");
      $sentencia_aptos->bindParam(":id_condominio", $id_condominio);
      $sentencia_aptos->execute();
      $apartamentos = $sentencia_aptos->fetchAll(PDO::FETCH_ASSOC);

      $resultados = [];
      foreach ($apartamentos as $apto) {
         $resultado = actualizarBalanceApto($apto['id'], $id_condominio);
         $resultados[$apto['apto']] = $resultado;
      }

      return [
         'success' => true,
         'total_aptos' => count($apartamentos),
         'resultados' => $resultados
      ];
   } catch (Exception $e) {
      return [
         'success' => false,
         'error' => $e->getMessage()
      ];
   }
}

// Ejecutar automáticamente si se pasan los parámetros
if (isset($_GET['id_apto']) && isset($_GET['id_condominio'])) {
   $resultado = actualizarBalanceApto($_GET['id_apto'], $_GET['id_condominio']);
   header('Content-Type: application/json');
   echo json_encode($resultado);
   exit;
}

if (isset($_GET['actualizar_todos']) && isset($_GET['id_condominio'])) {
   $resultado = actualizarTodosBalances($_GET['id_condominio']);
   header('Content-Type: application/json');
   echo json_encode($resultado);
   exit;
}
