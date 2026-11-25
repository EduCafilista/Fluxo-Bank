(()=>{'use strict';
const $=(s,scope=document)=>scope.querySelector(s);
const sidebar = $('#sidebar');
const overlay = $('[data-sidebar-overlay]');
const menu = $('[data-toggle-sidebar]');
function atualizarMenu(aberto) {
  sidebar?.classList.toggle('open', aberto);
  overlay?.classList.toggle('show', aberto);
  menu?.setAttribute('aria-expanded', String(aberto));
}
menu?.addEventListener('click', () => {
  const aberto = !sidebar?.classList.contains('open');
  atualizarMenu(aberto);
  if (aberto) $('.side-nav a', sidebar)?.focus();
});
overlay?.addEventListener('click', () => { atualizarMenu(false); menu?.focus(); });
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && sidebar?.classList.contains('open')) {
    atualizarMenu(false);
    menu?.focus();
  }
});
for(const btn of document.querySelectorAll('[data-toggle-password]')){btn.addEventListener('click',()=>{const input=document.getElementById(btn.dataset.togglePassword);if(input){input.type=input.type==='password'?'text':'password';btn.setAttribute('aria-label',input.type==='password'?'Mostrar senha':'Ocultar senha')}})}
for(const btn of document.querySelectorAll('[data-toggle-balance]')){btn.addEventListener('click',()=>{const element=$('[data-balance-amount]');if(!element)return;const hidden=element.dataset.hidden==='1';element.textContent=hidden?element.dataset.value:'R$ ••••••';element.dataset.hidden=hidden?'0':'1';btn.setAttribute('aria-label',hidden?'Ocultar saldo':'Mostrar saldo')})}
for(const btn of document.querySelectorAll('[data-copy]')){btn.addEventListener('click',async()=>{const value=btn.dataset.copy||document.getElementById(btn.dataset.copyTarget)?.textContent?.trim();if(!value)return;try{await navigator.clipboard.writeText(value);const old=btn.textContent;btn.textContent='Copiado!';setTimeout(()=>btn.textContent=old,1600)}catch(_){alert('Selecione o texto para copiar.')}})}
const dlg=$('#action-confirm');let pending=null;for(const form of document.querySelectorAll('form[data-confirm]')){form.addEventListener('submit',e=>{if(form.dataset.confirmed==='yes')return;e.preventDefault();pending=form;const message=$('#confirm-message');if(message)message.textContent=form.dataset.confirm||'Confira os dados antes de continuar.';if(dlg?.showModal)dlg.showModal();else if(window.confirm(message?.textContent||'Confirma esta operação?')){form.dataset.confirmed='yes';form.requestSubmit()}})}
$('[data-cancel-confirm]')?.addEventListener('click',()=>{dlg?.close();pending=null});$('[data-accept-confirm]')?.addEventListener('click',()=>{if(pending){const form=pending;pending=null;dlg?.close();form.dataset.confirmed='yes';form.requestSubmit()}});dlg?.addEventListener('close',()=>{pending=null});
for (const form of document.querySelectorAll('[data-registration]')) {
  const perfil = $('[data-register-role]', form);
  const tipo = $('[data-register-type]', form);
  function mostrar(seletor, visivel) {
    const bloco = $(seletor, form);
    bloco.hidden = !visivel;
    for (const campo of bloco.querySelectorAll('input, select')) {
      campo.disabled = !visivel;
      if (campo.tagName === 'INPUT') campo.required = visivel;
    }
  }
  function atualizarCadastro() {
    const cliente = !perfil || perfil.value === 'CLIENTE';
    const pj = cliente && tipo.value === 'PJ';
    mostrar('[data-register-client]', cliente);
    mostrar('[data-register-name]', !pj);
    mostrar('[data-register-pf]', cliente && !pj);
    mostrar('[data-register-pj]', pj);
  }
  perfil?.addEventListener('change', atualizarCadastro);
  tipo.addEventListener('change', atualizarCadastro);
  atualizarCadastro();
}
for(const sel of document.querySelectorAll('[data-key-type]')){sel.addEventListener('change',()=>{const input=$('[data-key-value]');if(!input)return;input.disabled=sel.value==='ALEATORIA';input.required=sel.value!=='ALEATORIA';input.placeholder=sel.value==='ALEATORIA'?'Gerada automaticamente':{CPF:'Somente números',CNPJ:'Somente números',EMAIL:'nome@exemplo.com',TELEFONE:'+55 34 99999-9999'}[sel.value]||'Informe sua chave';if(input.disabled)input.value=''})}
})();
