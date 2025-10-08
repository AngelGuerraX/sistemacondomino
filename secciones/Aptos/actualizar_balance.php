<?php
// actualizar_balance.php - ELIMINAR las redirecciones automáticas

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
      $total_actualizados = 0;

      foreach ($apartamentos as $apto) {
         $resultado = actualizarBalanceApto($apto['id'], $id_condominio);
         $resultados[$apto['apto']] = $resultado;
         if ($resultado['success']) {
            $total_actualizados++;
         }
      }

      return [
         'success' => true,
         'total_aptos' => count($apartamentos),
         'total_actualizados' => $total_actualizados,
         'resultados' => $resultados
      ];
   } catch (Exception $e) {
      return [
         'success' => false,
         'error' => $e->getMessage()
      ];
   }
}

// ELIMINAR las ejecuciones automáticas al final del archivo
// NO incluir código que se ejecute automáticamente aquí
