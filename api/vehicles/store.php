<?php

session_start();

require_once '../../config/database.php'; // Must create $pdo

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard/vehicles/index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/

function clean($value)
{
    return htmlspecialchars(trim($value));
}

/*
|--------------------------------------------------------------------------
| Vehicle Details
|--------------------------------------------------------------------------
*/

$seller_person_id         = !empty($_POST['seller_person_id']) ? $_POST['seller_person_id'] : null;
$vehicle_source           = clean($_POST['vehicle_source'] ?? '');
$current_owner_person_id  = !empty($_POST['current_owner_person_id']) ? $_POST['current_owner_person_id'] : null;
$current_location         = clean($_POST['current_location'] ?? '');

$advertised               = isset($_POST['advertised']) ? 1 : 0;
$available_for_sale       = isset($_POST['available_for_sale']) ? 1 : 0;

$purchase_date            = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
$purchase_price           = !empty($_POST['purchase_price']) ? $_POST['purchase_price'] : null;
$acquisition_type         = clean($_POST['acquisition_type'] ?? '');

$stock_number             = clean($_POST['stock_number'] ?? '');
$vin                      = clean($_POST['vin_number'] ?? '');
$registration_number      = clean($_POST['registration_number'] ?? '');

$make                     = clean($_POST['make'] ?? '');
$model                    = clean($_POST['model'] ?? '');
$variant                  = clean($_POST['variant'] ?? '');

$manufacture_year         = !empty($_POST['year']) ? $_POST['year'] : null;

$price                    = !empty($_POST['selling_price']) ? $_POST['selling_price'] : 0;
$mileage                  = !empty($_POST['mileage']) ? $_POST['mileage'] : 0;

$colour                   = clean($_POST['colour'] ?? '');
$body_type                = clean($_POST['body_type'] ?? '');

$fuel_type                = clean($_POST['fuel_type'] ?? '');
$transmission             = clean($_POST['transmission'] ?? '');

$drivetrain               = clean($_POST['drivetrain'] ?? '');
$engine_size              = clean($_POST['engine_size'] ?? '');
$engine_number            = clean($_POST['engine_number'] ?? '');

$doors                    = !empty($_POST['doors']) ? $_POST['doors'] : null;
$seats                    = !empty($_POST['seats']) ? $_POST['seats'] : null;

$condition_type           = clean($_POST['condition_type'] ?? '');

$service_history          = isset($_POST['service_history']) ? 1 : 0;
$accident_history         = isset($_POST['accident_history']) ? 1 : 0;

$damage_status            = clean($_POST['damage_status'] ?? '');
$damage_description       = clean($_POST['damage_description'] ?? '');

$roadworthy               = isset($_POST['roadworthy']) ? 1 : 0;
$warranty                 = isset($_POST['warranty']) ? 1 : 0;

$description              = clean($_POST['description'] ?? '');

$views                    = 0;
$featured                 = 0;
$status                   = 'Active';

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if (
    empty($stock_number) ||
    empty($vin) ||
    empty($make) ||
    empty($model) ||
    empty($manufacture_year) ||
    empty($price)
) {

    $_SESSION['error'] = "Please complete all required fields.";

    header("Location: ../../dashboard/vehicles/add.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Insert Vehicle
|--------------------------------------------------------------------------
*/

try {

    $sql = "INSERT INTO vehicles (

        seller_person_id,
        vehicle_source,
        current_owner_person_id,
        current_location,
        advertised,
        available_for_sale,
        purchase_date,
        purchase_price,
        acquisition_type,
        stock_number,
        vin,
        registration_number,
        make,
        model,
        variant,
        manufacture_year,
        price,
        mileage,
        colour,
        body_type,
        fuel_type,
        transmission,
        drivetrain,
        engine_size,
        engine_number,
        doors,
        seats,
        condition_type,
        service_history,
        accident_history,
        damage_status,
        damage_description,
        roadworthy,
        warranty,
        description,
        views,
        featured,
        status

    ) VALUES (

        :seller_person_id,
        :vehicle_source,
        :current_owner_person_id,
        :current_location,
        :advertised,
        :available_for_sale,
        :purchase_date,
        :purchase_price,
        :acquisition_type,
        :stock_number,
        :vin,
        :registration_number,
        :make,
        :model,
        :variant,
        :manufacture_year,
        :price,
        :mileage,
        :colour,
        :body_type,
        :fuel_type,
        :transmission,
        :drivetrain,
        :engine_size,
        :engine_number,
        :doors,
        :seats,
        :condition_type,
        :service_history,
        :accident_history,
        :damage_status,
        :damage_description,
        :roadworthy,
        :warranty,
        :description,
        :views,
        :featured,
        :status

    )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        ':seller_person_id'        => $seller_person_id,
        ':vehicle_source'          => $vehicle_source,
        ':current_owner_person_id' => $current_owner_person_id,
        ':current_location'        => $current_location,
        ':advertised'              => $advertised,
        ':available_for_sale'      => $available_for_sale,
        ':purchase_date'           => $purchase_date,
        ':purchase_price'          => $purchase_price,
        ':acquisition_type'        => $acquisition_type,
        ':stock_number'            => $stock_number,
        ':vin'                     => $vin,
        ':registration_number'     => $registration_number,
        ':make'                    => $make,
        ':model'                   => $model,
        ':variant'                 => $variant,
        ':manufacture_year'        => $manufacture_year,
        ':price'                   => $price,
        ':mileage'                 => $mileage,
        ':colour'                  => $colour,
        ':body_type'               => $body_type,
        ':fuel_type'               => $fuel_type,
        ':transmission'            => $transmission,
        ':drivetrain'              => $drivetrain,
        ':engine_size'             => $engine_size,
        ':engine_number'           => $engine_number,
        ':doors'                   => $doors,
        ':seats'                   => $seats,
        ':condition_type'          => $condition_type,
        ':service_history'         => $service_history,
        ':accident_history'        => $accident_history,
        ':damage_status'           => $damage_status,
        ':damage_description'      => $damage_description,
        ':roadworthy'              => $roadworthy,
        ':warranty'                => $warranty,
        ':description'             => $description,
        ':views'                   => $views,
        ':featured'                => $featured,
        ':status'                  => $status

    ]);

    $vehicle_id = $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Upload Vehicle Image
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES['vehicle_image']) &&
        $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK
    ) {

        $uploadDir = "../../uploads/vehicles/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION);

        $filename = uniqid('vehicle_') . "." . $extension;

        $destination = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $destination)) {

            $image = $pdo->prepare("
                INSERT INTO vehicle_images
                (
                    vehicle_id,
                    image_path,
                    image_title,
                    image_type,
                    is_primary,
                    display_order,
                    uploaded_at
                )
                VALUES
                (
                    :vehicle_id,
                    :image_path,
                    :image_title,
                    :image_type,
                    :is_primary,
                    :display_order,
                    NOW()
                )
            ");

            $image->execute([

                ':vehicle_id'    => $vehicle_id,
                ':image_path'    => $filename,
                ':image_title'   => 'Main Image',
                ':image_type'    => 'Exterior',
                ':is_primary'    => 1,
                ':display_order' => 1

            ]);

        }

    }

    $_SESSION['success'] = "Vehicle added successfully.";

} catch (PDOException $e) {

    $_SESSION['error'] = "Error: " . $e->getMessage();

}

header("Location: ../../dashboard/vehicles/index.php");
exit;