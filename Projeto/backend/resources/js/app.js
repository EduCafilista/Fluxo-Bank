import '../../public/assets/fluxo.js';
import QRCode from 'qrcode';
for (const canvas of document.querySelectorAll('canvas[data-pix-qr]')) {
  QRCode.toCanvas(canvas, canvas.dataset.pixQr, { width: 220, margin: 2, errorCorrectionLevel: 'M' })
    .catch(() => { canvas.style.display = 'none'; });
}

const arquivoPix = document.querySelector('[data-pix-image]');
if (arquivoPix && 'createImageBitmap' in window) {
  const status = document.querySelector('#pix-image-status');
  arquivoPix.closest('[data-pix-image-control]').hidden = false;
  arquivoPix.addEventListener('change', async () => {
    const arquivo = arquivoPix.files[0];
    if (!arquivo) return;
    if (arquivo.size > 10 * 1024 * 1024) {
      status.textContent = 'Selecione uma imagem de até 10 MB.';
      return;
    }

    try {
      const { default: lerQr } = await import('jsqr');
      const imagem = await createImageBitmap(arquivo);
      const canvas = document.createElement('canvas');
      const escala = Math.min(1, 1200 / Math.max(imagem.width, imagem.height));
      canvas.width = Math.max(1, Math.round(imagem.width * escala));
      canvas.height = Math.max(1, Math.round(imagem.height * escala));
      const contexto = canvas.getContext('2d');
      contexto.drawImage(imagem, 0, 0, canvas.width, canvas.height);
      imagem.close();
      const pixels = contexto.getImageData(0, 0, canvas.width, canvas.height);
      const resultado = lerQr(pixels.data, pixels.width, pixels.height);
      if (resultado?.data?.startsWith('000201') && resultado.data.length <= 512) {
        document.querySelector('#payload_emv').value = resultado.data;
        status.textContent = 'QR Code lido. Revise os dados da cobrança.';
      } else {
        status.textContent = 'Selecione uma imagem nítida do QR Pix ou cole o código.';
      }
    } catch {
      status.textContent = 'Selecione outra imagem ou cole o código Pix.';
    } finally {
      arquivoPix.value = '';
    }
  });
}
