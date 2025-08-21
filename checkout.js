(function(){
  const CART_KEY = 'mauiz-cart-v1';
  const loadCart = () => { try { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); } catch { return []; } };
  const saveCart = (items) => localStorage.setItem(CART_KEY, JSON.stringify(items));
  const money = (n) => (n||0).toFixed(2);

  const els = {
    orderList: document.getElementById('orderList'),
    subtotal: document.getElementById('subtotal'),
    discount: document.getElementById('discount'),
    payable: document.getElementById('payable'),
    cartCount: document.getElementById('cartCount'),
    cartTotal: document.getElementById('cartTotal'),
    couponInput: document.getElementById('couponInput'),
    applyCoupon: document.getElementById('applyCoupon'),
    placeOrder: document.getElementById('placeOrder'),
    custName: document.getElementById('custName'),
    custPhone: document.getElementById('custPhone'),
    custAddr: document.getElementById('custAddr')
  };

  let couponValue = 0;

  function calc(cart){
    let sub = 0; let count = 0;
    for(const item of cart){
      const extrasTotal = (item.extras||[]).reduce((s,e)=> s + (e.price * e.qty), 0);
      sub += (item.price + extrasTotal) * item.qty;
      count += item.qty;
    }
    const discount = Math.min(couponValue, sub);
    const total = sub - discount;
    els.subtotal.textContent = money(sub);
    els.discount.textContent = money(discount);
    els.payable.textContent = money(total);
    els.cartCount.textContent = count;
    els.cartTotal.textContent = money(total);
  }

  function render(){
    const cart = loadCart();
    els.orderList.innerHTML = cart.map((item, idx)=>{
      const extras = (item.extras||[]).map(e=> e.qty>0 ? `<span class="badge text-bg-light me-1">${e.name}</span>` : '').join(' ');
      return `
        <div class="card">
          <div class="card-body d-flex align-items-start gap-3">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 mb-1">
                <div class="btn-group btn-group-sm" role="group" aria-label="qty">
                  <button type="button" class="btn btn-outline-secondary" data-minus="${idx}">-</button>
                  <button type="button" class="btn btn-outline-secondary" disabled>${item.qty}</button>
                  <button type="button" class="btn btn-outline-secondary" data-plus="${idx}">+</button>
                </div>
                <div class="ms-2 fw-bold">${item.name}</div>
                <div class="ms-auto">₱ ${(item.price).toFixed(2)}</div>
              </div>
              <div class="text-muted small">${extras || ''}</div>
              ${item.notes? `<div class="small mt-1">Notes: ${item.notes}</div>`:''}
            </div>
            <button class="btn btn-sm btn-link text-danger" data-remove="${idx}"><i class="fa-solid fa-xmark"></i></button>
          </div>
        </div>`;
    }).join('');

    // attach events
    els.orderList.querySelectorAll('[data-minus]').forEach(btn=> btn.addEventListener('click', ()=> changeQty(+btn.getAttribute('data-minus'), -1)));
    els.orderList.querySelectorAll('[data-plus]').forEach(btn=> btn.addEventListener('click', ()=> changeQty(+btn.getAttribute('data-plus'), +1)));
    els.orderList.querySelectorAll('[data-remove]').forEach(btn=> btn.addEventListener('click', ()=> removeItem(+btn.getAttribute('data-remove'))));

    calc(cart);
  }

  function changeQty(index, delta){
    const cart = loadCart();
    if(!cart[index]) return;
    cart[index].qty = Math.max(1, cart[index].qty + delta);
    saveCart(cart); render();
  }

  function removeItem(index){
    const cart = loadCart();
    cart.splice(index,1);
    saveCart(cart); render();
  }

  els.applyCoupon.addEventListener('click', ()=>{
    const code = (els.couponInput.value || '').trim().toUpperCase();
    // simple demo coupon logic
    if(code === 'PROMO10') couponValue = 10; else if(code === 'PROMO20') couponValue = 20; else couponValue = 0;
    render();
  });

  els.placeOrder.addEventListener('click', ()=>{
    const cart = loadCart();
    if(cart.length === 0){ alert('Your cart is empty.'); return; }
    const name = (els.custName.value||'').trim();
    const phone = (els.custPhone.value||'').trim();
    if(!name || !phone){ alert('Please enter your name and phone.'); return; }

    const payload = {
      customer: { name, phone, address: (els.custAddr.value||'').trim(), type: document.querySelector('input[name="otype"]:checked') ? 'selected' : 'pickup' },
      items: cart,
      totals: {
        subtotal: parseFloat(els.subtotal.textContent || '0'),
        discount: parseFloat(els.discount.textContent || '0'),
        payable: parseFloat(els.payable.textContent || '0')
      }
    };

    fetch('api/orders.php', { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(payload) })
      .then(r=> r.json())
      .then(res =>{
        if(!res || !res.ok){ throw new Error(res && res.error ? res.error : 'Unknown error'); }
        alert('Order placed! ID: ' + res.order.id);
        localStorage.removeItem(CART_KEY);
        window.location.href = 'store.html';
      })
      .catch(err => alert('Failed to place order: ' + err.message));
  });

  render();
})();

