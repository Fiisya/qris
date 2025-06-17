<?php
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment; 
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
$merchantCity = "JAKARTA";     
$merchantMCC = "5411";
$tag00_GUID = "ID.CO.QRISDOMPETXYZ.WWW"; 
$tag01_MerchantID = "123456789012345";   
$merchantAccountPayload = "00" . str_pad(strlen($tag00_GUID), 2, '0', STR_PAD_LEFT) . $tag00_GUID .
                          "01" . str_pad(strlen($tag01_MerchantID), 2, '0', STR_PAD_LEFT) . $tag01_MerchantID;
$tag26_MerchantAccountInfo = "26" . str_pad(strlen($merchantAccountPayload), 2, '0', STR_PAD_LEFT) . $merchantAccountPayload;

// -------------------------------------------------------------------

$qrCodeUri = null;
$inputAmount = '';
$errorMessage = '';
$displayAmount = '';
function crc16_ccitt_false($data) {
    $crc = 0xFFFF;
    $length = strlen($data);
    for ($i = 0; $i < $length; $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
        }
    }
    return $crc & 0xFFFF;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputAmount = isset($_POST['amount']) ? trim($_POST['amount']) : '';

    if (is_numeric($inputAmount) && $inputAmount > 0) {
        $amountValue = number_format(floatval($inputAmount), 0, '', ''); 
        $displayAmount = number_format(floatval($inputAmount), 0, ',', '.');
        $payload = "";
        $payload .= "000201";
        $payload .= "010212";
        $payload .= $tag26_MerchantAccountInfo;
        $payload .= "52" . str_pad(strlen($merchantMCC), 2, '0', STR_PAD_LEFT) . $merchantMCC;
        $payload .= "5303360";
        $payload .= "54" . str_pad(strlen($amountValue), 2, '0', STR_PAD_LEFT) . $amountValue;
        $payload .= "5802ID";
        $payload .= "59" . str_pad(strlen($merchantName), 2, '0', STR_PAD_LEFT) . $merchantName;
        $payload .= "60" . str_pad(strlen($merchantCity), 2, '0', STR_PAD_LEFT) . $merchantCity;
        $crcValue = crc16_ccitt_false($payload);
        $payload .= "6304" . strtoupper(sprintf('%04X', $crcValue));
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($payload)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->validateResult(false)
                ->build();

            $qrCodeUri = $result->getDataUri();

        } catch (Exception $e) {
            $errorMessage = "Gagal membuat QR Code: " . $e->getMessage();;
        }

    } else {
        $errorMessage = "Masukkan jumlah (harga) yang valid (angka positif).";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator QRIS Dinamis</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; margin: 0; background-color: #f8f9fa; color: #212529; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box;}
        .container { max-width: 500px; width:100%; background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #343a40; margin-bottom: 25px; font-size: 1.8em; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; }
        input[type="number"], input[type="text"] { 
            width: 100%; 
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box; 
            font-size: 1rem;
        }
        input[type="number"]:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            transition: background-color 0.15s ease-in-out;
        }
        button:hover { background-color: #0056b3; }
        .qr-code { text-align: center; margin-top: 25px; }
        .qr-code img { border: 1px solid #dee2e6; padding: 5px; background: white; max-width: 100%; height: auto; }
        .qr-info { margin-top:10px; font-size:0.9em; color: #6c757d; }
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center;}
        .info-merchant { margin-bottom: 20px; padding: 12px; background-color: #e2e3e5; border-left: 5px solid #007bff; font-size: 0.9em; }
        .warning { background-color: #fff3cd; color: #856404; padding: 12px; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px; font-size: 0.9em;}
    </style>
</head>
<body>
    <div class="container">
        <h1>Generator QRIS Dinamis</h1>

        <div class="warning">
            <strong>PENTING:</strong> Data merchant (Nama Toko, ID Merchant, GUID, dll.) dalam contoh ini adalah dummy.
            Ganti dengan data QRIS statis valid Anda pada bagian konfigurasi di kode PHP agar QRIS ini dapat digunakan untuk transaksi nyata.
        </div>
        
        <div class="info-merchant">
            <strong>Merchant:</strong> <?php echo htmlspecialchars($merchantName); ?><br>
            <strong>Kota:</strong> <?php echo htmlspecialchars($merchantCity); ?>
        </div>

        <form method="POST" action="">
            <div>
                <label for="amount">Masukkan Jumlah Harga (IDR):</label>
                <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($inputAmount); ?>" placeholder="Contoh: 50000" required min="1" step="any">
            </div>
            <button type="submit">Buat QRIS</button>
        </form>

        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <?php if ($qrCodeUri): ?>
            <div class="qr-code">
                <h3>Scan QRIS untuk Pembayaran Rp <?php echo htmlspecialchars($displayAmount); ?></h3>
                <img src="<?php echo $qrCodeUri; ?>" alt="QRIS Dinamis - <?php echo htmlspecialchars($merchantName); ?>">
                <p class="qr-info"><small>Ditujukan kepada: <?php echo htmlspecialchars($merchantName); ?></small></p>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>