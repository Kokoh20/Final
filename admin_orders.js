(function(){
  const container = document.getElementById('ordersContainer');
  const refreshBtn = document.getElementById('refreshBtn');

  function money(n){ return (n||0).toFixed(2); }

  function render(list){
    if(!list.length){ container.innerHTML = '<div class="alert alert-info">No orders yet.</div>'; return; }
    container.innerHTML = list.map(o=>{
      const items = (o.items||[]).map(i=> `${i.quantity||i.qty}× ${i.product_name||i.name}`).join(', ');
      return `
        <div class="card">
          <div class="card-body d-flex gap-3 align-items-start">
            <div class="flex-grow-1">
              <div class="fw-bold">${o.customer.name} • ${o.customer.phone}</div>
              <div class="text-muted small">${o.customer.address || ''}</div>
              <div class="small">${items}</div>
            </div>
            <div class="text-end">
              <div class="fw-bold">₱ ${money((o.totals && o.totals.payable) || o.total)}</div>
              <div class="small text-muted">${o.created_at || o.createdAt}</div>
            </div>
          </div>
        </div>`;
    }).join('');
  }

  function load(){
    fetch('api/orders.php')
      .then(r=> r.json())
      .then(res => render(res.orders || []))
      .catch(()=> container.innerHTML = '<div class="alert alert-danger">Failed to load orders.</div>');
  }

  refreshBtn.addEventListener('click', load);
  load();
})();

