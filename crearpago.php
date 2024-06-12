 <?php
///*//funcion para insertar un pago en transfermovil

///estos son los parametros que tiene q pasar cuando vas a insertar un pago
$amount= 'Precio de la Orden';
$externalId = 'Numero de la orden';
$orderId = 'Id de la orden esta se usa para guardar el qr';
public function insertarPagoTransferMovil($amount, $externalId, $orderId) {
    // Datos necesarios para la solicitud de pago
    $telefono = 'telefono';
    $Description = 'Pago Transfermovil';
    $usuario = ''; // Nombre de usuario
    $semilla = '' // Semilla de autenticación
    $source = ''; // Identificador de Entidad

    date_default_timezone_set('America/Havana');
    $fecha = getdate();

    $year = $fecha['year'];
    $mes = $fecha['mon'];
    $dia = $fecha['mday'];

    // Generar contraseña de autenticación
    $password = base64_encode(hash('sha512', $usuario . $dia.$mes.$year. $semilla . $source, true));

    // URL de la API
    $url = 'https://152.206.64.213:15000/RestExternalPayment.svc/payOrder';

    // Datos de la solicitud de pago
    $data = array(
        'request' => array(
            'Amount' => $amount,
            'Phone' => $telefono,
            'Currency' => 'CUP',//moneda 
            'Description' => $Description,
            'ExternalId' => $externalId,
            'Source' => $source,
            'UrlResponse' => 'url de donde esta el endpoin q recibe las respuestas de transfermovil',
            'ValidTime' => '0'///tiempo valido de la orden en segundos 0 la orden no vence
        )
    );

    // Convertir datos a formato JSON
    $data_json = json_encode($data);

    // Configurar opciones de solicitud
    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/json\r\n" .
                         "username: $usuario\r\n" .
                         "source: $source\r\n" .
                         "password: $password\r\n",
            'method'  => 'POST',
            'content' => $data_json
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    );

    // Crear contexto de solicitud
    $context  = stream_context_create($options);

    // Realizar solicitud a la API
    $response = file_get_contents($url, false, $context);

    // Manejar la respuesta
    if ($response === false) {
        $respuesta = false;
        return $respuesta;
    } else {
        // Decodificar la respuesta JSON
        $response_data = json_decode($response, true);

        // Manejar la respuesta según el resultado
        if ($response_data['PayOrderResult']['Success']) {
            // Datos para el código QR
            $qrData = "{'id_transaccion': '$externalId', 'importe': $amount, 'moneda': 'CUP', 'numero_proveedor': '$source', 'version': 1}";

            // Crear el objeto QrCode
          //nota usted puede crear el qr con la libreria de su preferencia solo debe de pasarle como parametros la vaiable $qrData
          
            $qrCode = new QrCode($qrData);

            // Ruta para guardar la imagen del código QR
            $path = public_path('Ruta para guardar la imagen del código QR');
            $fileName = $orderId.'.png';

            // Guardar la imagen del código QR
            $qrCode->writeFile($path . $fileName);

            // Ruta de la imagen del código QR
            $rutaImagen = ' // Ruta de la imagen del código QR/'.$orderId.'.png';

            // Enlace para abrir la app TransferMovil
            $link_transfer = "transfermovil://tm_compra_en_linea/action?id_transaccion=$externalId&importe=$amount&moneda=CUP&numero_proveedor=$source";

            // Respuesta con la ruta de la imagen del código QR y el enlace de TransferMovil
            $respuesta = [
                'rutaImagen' => $rutaImagen,
                'link_transfer' => $link_transfer
            ];
          ///esta es la respuesta del qr generado y ya la orden insertada en Transfermovil. La variable respuesta debe de mostarla en la vista
            return $respuesta;
        }
    }
}
