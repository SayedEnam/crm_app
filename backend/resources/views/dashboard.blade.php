@extends('layouts.app')
@section('title', 'Dashboard · Northstar CRM')
@section('body')
<div class="shell">
    <aside class="side">
        <div class="brand"><b>N</b>Northstar CRM</div>
        <nav class="nav"><div class="nav-label">Workspace</div><a href="{{ route('dashboard') }}" class="active"><span class="ico">◫</span>Dashboard</a><a href="/api/clients"><span class="ico">♙</span>Clients</a><a href="/api/applications"><span class="ico">▣</span>Applications</a><a href="/api/follow-ups"><span class="ico">◷</span>Follow-ups</a>@if(auth()->user()->hasPermission('payments.view'))<a href="/api/payments"><span class="ico">＄</span>Payments</a>@endif<div class="nav-label" style="margin-top:22px">Account</div><a href="{{ route('profile.edit') }}"><span class="ico">◉</span>My profile</a></nav>
        <div class="side-foot"><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" style="width:100%;background:#1c2a48;color:white;border-color:#34415d">Sign out</button></form></div>
    </aside>
    <main class="main">
        <header class="top"><span class="crumb">Workspace / Overview</span><div class="userbox"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>{{ auth()->user()->name }}</div></header>
        <div class="page">
            <div class="heading"><div><h1>Dashboard</h1><div class="muted">Here’s what’s happening across your workspace.</div></div><div class="actions"><div class="filters"><input type="date" id="from"><span class="muted">to</span><input type="date" id="to"><button class="btn" id="apply">Apply</button></div></div></div>
            <div class="actions" style="margin-bottom:16px"><a class="btn primary" href="/api/clients">Browse clients</a><a class="btn" href="/api/applications">Browse applications</a><a class="btn" href="/api/follow-ups">Open follow-ups</a>@if(auth()->user()->hasPermission('payments.view'))<a class="btn" href="/api/payments">Open payments</a>@endif</div>
            <div id="message" class="loading">Loading live dashboard…</div>
            <section id="stats" class="stats" aria-live="polite"></section>
            <section class="grid">
                <article class="panel"><div class="panel-head"><h2>Monthly registrations</h2><span class="muted" id="rangeLabel"></span></div><div class="chart" id="registrations"></div></article>
                <article class="panel"><div class="panel-head"><h2>Application status</h2></div><div class="legend" id="statuses"></div></article>
                <article class="panel"><div class="panel-head"><h2>Recent clients</h2><a class="muted" href="/api/clients">View all</a></div><table class="table"><thead><tr><th>Client</th><th>Status</th><th>Registered</th></tr></thead><tbody id="clients"></tbody></table></article>
                <article class="panel"><div class="panel-head"><h2>Recent applications</h2><a class="muted" href="/api/applications">View all</a></div><table class="table"><thead><tr><th>Client</th><th>Service</th><th>Status</th></tr></thead><tbody id="applications"></tbody></table></article>
                <article class="panel"><div class="panel-head"><h2>Service statistics</h2></div><div class="legend" id="services"></div></article>
                <article class="panel"><div class="panel-head"><h2>Upcoming follow-ups</h2><a class="muted" href="/api/follow-ups">View all</a></div><div id="followups"></div></article>
            </section>
        </div>
    </main>
</div>
<script>
const labels={total_clients:'Total clients',new_clients_this_month:'New clients this month',total_leads:'Total leads',active_applications:'Active applications',pending_documents:'Pending documents',follow_ups_due_today:'Follow-ups due today',overdue_follow_ups:'Overdue follow-ups',outstanding_payments:'Outstanding payments',total_collections:'Total collections',completed_applications:'Completed applications'};
const icons=['♙','↗','◎','▣','▤','◷','!','＄','↗','✓'];
const money=new Intl.NumberFormat(undefined,{style:'currency',currency:'USD',maximumFractionDigits:0});
const safe=(v)=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function rowEmpty(n,span){return '<tr><td colspan="'+span+'" class="empty">'+n+'</td></tr>'}
async function load(){
 const msg=document.getElementById('message');msg.textContent='Loading live dashboard…';
 const params=new URLSearchParams({from:document.getElementById('from').value,to:document.getElementById('to').value});
 try{
  const response=await fetch('/api/dashboard?'+params,{headers:{Accept:'application/json'},credentials:'same-origin'});
  if(!response.ok)throw new Error(response.status===403?'Your role does not have dashboard access.':'Dashboard data could not be loaded.');
  const d=await response.json();msg.textContent='';
  document.getElementById('rangeLabel').textContent=d.filters.from+' — '+d.filters.to;
  document.getElementById('stats').innerHTML=Object.entries(labels).map(([key,label],i)=>'<article class="card"><div class="stat-label">'+label+'<span class="stat-icon">'+icons[i]+'</span></div><div class="stat-value">'+(d.stats[key]===null?'—':(key.includes('payments')||key==='total_collections'?money.format(Number(d.stats[key])):Number(d.stats[key]).toLocaleString()))+'</div><div class="stat-note">Live database total</div></article>').join('');
  const vals=d.charts.registrations, max=Math.max(1,...vals.map(x=>Number(x.total)));
  document.getElementById('registrations').innerHTML=vals.length?vals.map(x=>'<div class="barwrap" title="'+safe(x.month)+': '+x.total+' registrations"><div class="bar" style="height:'+Math.max(4,Number(x.total)/max*100)+'%"></div><small>'+safe(x.month)+'</small></div>').join(''):'<div class="empty" style="width:100%">No registrations in this date range.</div>';
  document.getElementById('statuses').innerHTML=d.charts.application_statuses.length?d.charts.application_statuses.map((x,i)=>'<div class="legend-row"><span class="legend-left"><i class="dot" style="opacity:'+Math.max(.35,1-i*.12)+'"></i>'+safe(x.status.replaceAll('_',' '))+'</span><b>'+Number(x.total).toLocaleString()+'</b></div>').join(''):'<div class="empty">No applications yet.</div>';
  document.getElementById('services').innerHTML=d.charts.services.length?d.charts.services.map(x=>'<div class="legend-row"><span class="legend-left"><i class="dot"></i>'+safe(x.service)+'</span><b>'+Number(x.total).toLocaleString()+'</b></div>').join(''):'<div class="empty">No service activity yet.</div>';
  document.getElementById('clients').innerHTML=d.recent_clients.length?d.recent_clients.map(x=>'<tr><td><b>'+safe(x.name)+'</b><br><span class="muted">'+safe(x.email||'No email')+'</span></td><td><span class="pill '+safe(x.status)+'">'+safe(x.status)+'</span></td><td>'+new Date(x.created_at).toLocaleDateString()+'</td></tr>').join(''):rowEmpty('No clients yet.',3);
  document.getElementById('applications').innerHTML=d.recent_applications.length?d.recent_applications.map(x=>'<tr><td>'+safe(x.client?.name||'—')+'</td><td>'+safe(x.service)+'</td><td><span class="pill '+safe(x.status)+'">'+safe(x.status.replaceAll('_',' '))+'</span></td></tr>').join(''):rowEmpty('No applications yet.',3);
  document.getElementById('followups').innerHTML=d.upcoming_follow_ups.length?d.upcoming_follow_ups.map(x=>{let dt=new Date(x.due_at);return '<div class="follow-row"><div class="datebox">'+dt.toLocaleDateString(undefined,{month:'short',day:'2-digit'})+'</div><div><b style="font-size:12px">'+safe(x.client?.name||'Client')+'</b><div class="muted" style="margin-top:4px">'+dt.toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'})+(x.notes?' · '+safe(x.notes):'')+'</div></div></div>'}).join(''):'<div class="empty">No upcoming follow-ups.</div>';
 }catch(e){msg.textContent=e.message;msg.className='toast'}
}
const today=new Date(), month=new Date(today.getFullYear(),today.getMonth(),1);
const localISO=d=>[d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');
document.getElementById('from').value=localISO(month);document.getElementById('to').value=localISO(today);
document.getElementById('apply').addEventListener('click',load);load();
</script>
@endsection
