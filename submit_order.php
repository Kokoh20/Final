<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mauiz_cafe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$name = $_POST['customerName'];
$contact = $_POST['contactNumber'];
$address = $_POST['deliveryAddress'];
$items = isset($_POST['items']) ? $_POST['items'] : [];

$total = 0;
$menu = [
  "Brewed Coffee" => 80,
  "Caramel Macchiato" => 120,
  "Chocolate Cake" => 90
];

foreach ($items as $item => $value) {
  if (array_key_exists($item, $menu)) {
    $total += $menu[$item];
  }
}


if (empty($items)) {
  echo "<h2>No items selected</h2><p>Please go back and choose at least one item.</p>";
  $conn->close();
  exit;
}

$stmt = $conn->prepare("INSERT INTO orders (customer_name, contact_number, delivery_address, total_price) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssd", $name, $contact, $address, $total);
$stmt->execute();
$order_id = $stmt->insert_id;

foreach ($items as $item => $value) {
  $quantity = 1;
  $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, item_name, quantity) VALUES (?, ?, ?)");
  $stmt_item->bind_param("isi", $order_id, $item, $quantity);
  $stmt_item->execute();
}

echo "<h2>Thank you for your order, $name!</h2><p>Your order has been successfully placed. Total: ₱$total</p>";

$conn->close();
?>