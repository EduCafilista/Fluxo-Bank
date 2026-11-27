# Melhorias Fluxo Bank: Pix, KYC e 2FA

As alterações deste branch são propostas para revisão. Não fazer merge antes de testar Laravel e PostgreSQL em homologação.

## Novos fluxos
- Chave Pix inexistente mostra erro no formulário.
- Cobranças com histórico, cancelamento e validade configurável (5 minutos por padrão); códigos guardados cifrados.
- Cadastro cria solicitação pendente por padrão; menu de solicitações no backoffice; aprovação/recusa com tentativa de e-mail pós-commit.
- 2FA por e-mail com reenvio limitado por rate-limit, sujeito a provedor de envio real.
- Pix Copia e Cola diretamente na página Pix e revisão antes do pagamento.

## Configuração no Render
FLUXO_KYC_AUTOMATICO=false
FLUXO_EMAIL_PROVIDER=resend
RESEND_API_KEY=(segredo no Render)
MAIL_FROM_ADDRESS=(domínio verificado)
MAIL_FROM_NAME="Fluxo Bank"
FLUXO_2FA_EMAIL_ENTREGA=true
FLUXO_2FA_CANAL=EMAIL

SMTP na hospedagem gratuita pode estar restrito; o modo Resend utiliza HTTPS. A aceitação pelo provedor não assegura entrega final.

**Nota**: não migrar o banco de produção sem backup e testes em homologação. Cobranças antigas não poderão reexibir os códigos, porque eles não eram persistidos.

Os Pix são simulados entre contas do próprio Fluxo Bank e não fazem liquidação bancária real.
