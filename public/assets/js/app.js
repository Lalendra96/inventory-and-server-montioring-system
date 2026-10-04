'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Are you sure?'))e.preventDefault()}));
 const pass=document.querySelector('[data-password-toggle]'); if(pass){pass.addEventListener('click',()=>{const input=document.querySelector(pass.dataset.passwordToggle); if(input) input.type=input.type==='password'?'text':'password';});}
});
