<?php
// actualizar_balance.php

function actualizarBalanceApto($id_apto, $id_condominio)
{
   global $conexion;

   // 1. Verificar si tiene inquilino activo
   $stmt = $conexion->prepare("SELECT tiene_inquilino FROM tbl_aptos WHERE id=:id");
   $stmt->execute([':id' => $id_apto]);
   $apto = $stmt->fetch(PDO::FETCH_ASSOC);
   $tiene_inquilino = ($apto['tiene_inquilino'] == 1);

   // =========================================================
   // A. CALCULAR DEUDA PROPIETARIO (Usando CAST para evitar error de texto)
   // =========================================================
   $deuda_prop = 0;

   // A1. Tickets (Mantenimiento + Mora - Abono)
   // Convertimos texto a decimal antes de sumar/restar
   $sql_ticket = "SELECT SUM( (CAST(mantenimiento AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2))) - abono ) 
                   FROM tbl_tickets 
                   WHERE id_apto=:id AND estado != 'Pagado'";
   $s_ticket = $conexion->prepare($sql_ticket);
   $s_ticket->execute([':id' => $id_apto]);
   $deuda_prop += floatval($s_ticket->fetchColumn());

   // A2. Cuotas Pendientes (Monto + Mora - Abono)
   $sql_cuota = "SELECT SUM( (monto + mora) - abono ) 
                  FROM tbl_cuotas_extras 
                  WHERE id_apto=:id AND estado != 'Pagado'";
   $s_cuota = $conexion->prepare($sql_cuota);
   $s_cuota->execute([':id' => $id_apto]);
   $deuda_prop += floatval($s_cuota->fetchColumn());

   // A3. Gas (Solo si NO hay inquilino)
   if (!$tiene_inquilino) {
      $sql_gas = "SELECT SUM( (total_gas + mora) - abono ) 
                    FROM tbl_gas 
                    WHERE id_apto=:id AND estado != 'Pagado'";
      $s_gas = $conexion->prepare($sql_gas);
      $s_gas->execute([':id' => $id_apto]);
      $deuda_prop += floatval($s_gas->fetchColumn());
   }

   // A4. Restar Adelantos (Saldo a Favor)
   // Esto suma todos los adelantos POSITIVOS y resta los NEGATIVOS (uso de saldo)
   $s_adelantos = $conexion->prepare("
        SELECT SUM(d.monto) 
        FROM tbl_pagos_detalle d
        INNER JOIN tbl_pagos p ON d.id_pago = p.id_pago
        WHERE p.id_apto = :id 
        AND d.tipo_deuda = 'adelanto' 
        AND d.tipo_pagador = 'propietario'
    ");
   $s_adelantos->execute([':id' => $id_apto]);
   $adelanto_prop = floatval($s_adelantos->fetchColumn());

   // A5. Balance Final Propietario
   // Fórmula: Deuda Real - Dinero a Favor
   $balance_final_prop = $deuda_prop - $adelanto_prop;


   // =========================================================
   // B. CALCULAR DEUDA INQUILINO (Si existe)
   // =========================================================
   $balance_final_inq = 0;

   if ($tiene_inquilino) {
      $s_inq = $conexion->prepare("SELECT id FROM tbl_inquilinos WHERE id_apto=:id AND activo=1");
      $s_inq->execute([':id' => $id_apto]);
      $inquilino = $s_inq->fetch(PDO::FETCH_ASSOC);

      if ($inquilino) {
         // B1. Deuda Gas Inquilino
         $sql_gas_inq = "SELECT SUM( (total_gas + mora) - abono ) 
                            FROM tbl_gas 
                            WHERE id_apto=:id AND estado != 'Pagado'";
         $s_gas_inq = $conexion->prepare($sql_gas_inq);
         $s_gas_inq->execute([':id' => $id_apto]);
         $deuda_inq = floatval($s_gas_inq->fetchColumn());

         // B2. Adelantos Inquilino
         $s_adelantos_inq = $conexion->prepare("
                SELECT SUM(d.monto) 
                FROM tbl_pagos_detalle d
                INNER JOIN tbl_pagos_inquilinos p ON d.id_pago = p.id
                WHERE p.id_inquilino = :id_inq 
                AND d.tipo_deuda = 'adelanto' 
                AND d.tipo_pagador = 'inquilino'
            ");
         $s_adelantos_inq->execute([':id_inq' => $inquilino['id']]);
         $adelanto_inq = floatval($s_adelantos_inq->fetchColumn());

         // B3. Balance Final Inquilino
         $balance_final_inq = $deuda_inq - $adelanto_inq;

         // Actualizar tabla Inquilinos
         $conexion->prepare("UPDATE tbl_inquilinos SET balance = :bal WHERE id=:id")
            ->execute([':bal' => $balance_final_inq, ':id' => $inquilino['id']]);
      }
   }

   // =========================================================
   // C. ACTUALIZAR APARTAMENTO (TOTAL GLOBAL)
   // =========================================================
   $gran_total = $balance_final_prop + $balance_final_inq;

   $upd = $conexion->prepare("UPDATE tbl_aptos SET balance = :bal WHERE id=:id");
   $upd->execute([':bal' => $gran_total, ':id' => $id_apto]);
}
