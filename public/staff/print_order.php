<?php
ob_start();
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_staff_login();

// Clear any previous output
ob_clean();

// Include TCPDF library
require_once __DIR__ . '/../../vendor/autoload.php';

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone_number as phone
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    ob_end_clean();
    die('Order not found');
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, mi.name, mi.image_url, oi.price_at_order as price_per_unit
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'Armaya Catering', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Armaya Catering');
$pdf->SetAuthor('Armaya Catering');
$pdf->SetTitle('Order #' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT));

// Set margins
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 11);

// Order number and date
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Order #' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT), 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 10, 'Order Date: ' . date('d/m/Y h:i A', strtotime($order['placed_at'])), 0, 1, 'L');
$pdf->Ln(5);

// Customer Information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Customer Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(30, 7, 'Name:', 0, 0);
$pdf->Cell(0, 7, $order['full_name'], 0, 1);
$pdf->Cell(30, 7, 'Phone:', 0, 0);
$pdf->Cell(0, 7, $order['phone'], 0, 1);
$pdf->Ln(5);

// Delivery Information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Delivery Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(40, 7, 'Delivery Date:', 0, 0);
$pdf->Cell(0, 7, date('d/m/Y', strtotime($order['delivery_date'])), 0, 1);
$pdf->Cell(40, 7, 'Delivery Time:', 0, 0);
$pdf->Cell(0, 7, date('h:i A', strtotime($order['delivery_time'])), 0, 1);
$pdf->Cell(40, 7, 'Location:', 0, 0);
$pdf->MultiCell(0, 7, $order['delivery_location'], 0, 'L');
$pdf->Cell(40, 7, 'Event Type:', 0, 0);
$pdf->Cell(0, 7, $order['event_type'], 0, 1);
$pdf->Cell(40, 7, 'Serving Method:', 0, 0);
$pdf->Cell(0, 7, $order['serving_method'], 0, 1);
$pdf->Cell(40, 7, 'Total Pax:', 0, 0);
$pdf->Cell(0, 7, $order['total_pax'], 0, 1);
$pdf->Ln(5);

// Order Items
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Order Items', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

// Table header
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(80, 8, 'Item', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'Quantity', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Price', 1, 0, 'R', true);
$pdf->Cell(35, 8, 'Total', 1, 1, 'R', true);

// Table content
foreach ($order_items as $item) {
    $pdf->MultiCell(80, 8, $item['name'], 1, 'L', false, 0);
    $pdf->Cell(30, 8, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(35, 8, 'RM ' . number_format($item['price_per_unit'], 2), 1, 0, 'R');
    $pdf->Cell(35, 8, 'RM ' . number_format($item['price_per_unit'] * $item['quantity'], 2), 1, 1, 'R');
}

// Total
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(145, 8, 'Total Amount:', 1, 0, 'R', true);
$pdf->Cell(35, 8, 'RM ' . number_format($order['total_amount'], 2), 1, 1, 'R', true);

// Special Instructions
if (!empty($order['staff_notes'])) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Special Instructions', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 7, $order['staff_notes'], 0, 'L');
}

// Clean output buffer before generating PDF
ob_end_clean();

// Output the PDF
$pdf->Output('Order_' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) . '.pdf', 'I'); 