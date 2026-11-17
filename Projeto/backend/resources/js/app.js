import '../../public/assets/fluxo.js';
import QRCode from 'qrcode';
for (const canvas of document.querySelectorAll('canvas[data-pix-qr]')) {
  QRCode.toCanvas(canvas, canvas.dataset.pixQr, { width: 220, margin: 2, errorCorrectionLevel: 'M' })
    .catch(() => { canvas.style.display = 'none'; });
}
