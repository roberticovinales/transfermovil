<?php
////end point respuesta de transfermovil


    // Obtener los datos de la solicitud
    $requestData = json_decode(file_get_contents('php://input'), true);   

    // Verificar si se recibieron los datos correctamente
    if ($requestData === null || !isset($requestData['ExternalId'])) {
        // Manejar el error de datos de solicitud incorrectos
        return response()->json(['error' => 'Datos de solicitud incorrectos'], 400);
    }

    // Parámetros de la URL
    $externalId = $requestData['ExternalId'];    
    $source = 00000; // Identificador de Entidad
    $usuario = 'usuario';// usuario de transfermovil
    $semilla = 'semilla'; // semilla_de_autenticacion' con el valor correcto    
    
  ///Obtener la fecha de cuba para q funcione con los servidores de Transfermovil  
  date_default_timezone_set('America/Havana');
    $fecha = getdate();
    $year = $fecha['year'];
    $mes = $fecha['mon'];
    $dia = $fecha['mday'];

    // URL para verificar la orden y saber el estado
    //puertos de tranfermovil
    //15001 puerto de pruebas 
    //15000 puerto de producion 
    $url = 'https://152.206.64.213:15000/RestExternalPayment.svc/getStatusOrder/'.$externalId.'/'.$source;

    // Generar contraseña de autenticación
    $password = base64_encode(hash('sha512', $usuario . $dia.$mes.$year. $semilla . $source, true));

    $context = stream_context_create([  
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "username: $usuario\r\n" .
                         "source: $source\r\n" .
                         "password: $password\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);

    // Realizar la solicitud GET con el contexto SSL y los encabezados de autenticación
    $response = file_get_contents($url, false, $context);

    // Verificar si la solicitud fue exitosa
    if ($response === false) {
        // Manejar el error de solicitud fallida
        return response()->json(['error' => 'Error al realizar la solicitud GET'], 500);
    }

    // Decodificar la respuesta JSON
    $data = json_decode($response, true);

    // Verificar si la decodificación fue exitosa
    if ($data === null || !isset($data['GetStatusOrderResult']['Status'])) {
        // Manejar el error de decodificación de respuesta JSON incorrecta
        return response()->json(['error' => 'Error al decodificar la respuesta JSON'], 500);
    }
    ///obtener el estado de la orden
    $status = $data['GetStatusOrderResult']['Status'];   
      
    if ($status == 3) {
     /* Actualizar la orden en la base de datos
hacer todo el proceso en su base de datos para cofirmar el pago

*/
  $updated = 'Consulta en el base de datos que quieres q realice si el pago es exitoso';

     if (!$updated) {
         // Manejar el error de actualización de la base de datos
         return response()->json(['error' => 'Error al actualizar la orden en la base de datos'], 500);
     }

     // Construir y devolver la respuesta en formato JSON
     return response()->json([
         "Success" => true,
         "Resultmsg" => "Mensaje ok",
         "Status" => 1
     ]);
 }

