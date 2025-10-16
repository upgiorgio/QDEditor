(function(){
  var cfg = window.QDCK5_CFG || {};

  var i18n = {
    'zh-CN': { preview:'预览开关', expHtml:'导出HTML', expMd:'导出MD', dark:'暗色切换', missing:'未检测到本地 CKEditor5，请在设置中选择 CDN 或放置本地构建。' },
    'zh-TW': { preview:'預覽開關', expHtml:'導出HTML', expMd:'導出MD', dark:'深色切換', missing:'未檢測到本地 CKEditor5，請在設置中選擇 CDN 或放置本地構建。' },
    'en':    { preview:'Toggle Preview', expHtml:'Export HTML', expMd:'Export MD', dark:'Dark Mode', missing:'Local CKEditor5 not found. Use CDN or provide local build.' }
  };
  var L = i18n[cfg.uiLang||'zh-CN'] || i18n['zh-CN'];

  function findTextarea(){
    var el = document.getElementById('text');
    if(el && el.tagName && el.tagName.toLowerCase()==='textarea') return el;
    var cands = document.getElementsByName('text');
    for(var i=0;i<cands.length;i++){
      if(cands[i].tagName && cands[i].tagName.toLowerCase()==='textarea') return cands[i];
    }
    return null;
  }

  function download(name, content, type){
    var blob = new Blob([content], {type:type||'text/plain;charset=utf-8'});
    var a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=name; a.click();
    setTimeout(function(){ URL.revokeObjectURL(a.href); }, 500);
  }

  function htmlToMd(html){
    var div=document.createElement('div'); div.innerHTML=html;
    function walk(n){
      var out='';
      for(var i=0;i<n.childNodes.length;i++){
        var c=n.childNodes[i];
        if(c.nodeType===3){ out+=c.nodeValue; continue; }
        if(c.nodeType!==1) continue;
        var t=c.nodeName.toLowerCase(), inner=walk(c);
        if(t==='h1') out+='# '+inner+'\\n\\n';
        else if(t==='h2') out+='## '+inner+'\\n\\n';
        else if(t==='h3') out+='### '+inner+'\\n\\n';
        else if(t==='p') out+=inner+'\\n\\n';
        else if(t==='blockquote') out+='> '+inner+'\\n\\n';
        else if(t==='strong'||t==='b') out+='**'+inner+'**';
        else if(t==='em'||t==='i') out+='*'+inner+'*';
        else if(t==='u') out+='__'+inner+'__';
        else if(t==='s'||t==='del') out+='~~'+inner+'~~';
        else if(t==='code' && c.parentNode && c.parentNode.nodeName.toLowerCase()==='pre') out+='```\\n'+c.textContent+'\\n```\\n\\n';
        else if(t==='code') out+='`'+inner+'`';
        else if(t==='pre') out+='```\\n'+c.textContent+'\\n```\\n\\n';
        else if(t==='ul'){ for(var j=0;j<c.children.length;j++){ out+='- '+c.children[j].textContent+'\\n'; } out+='\\n'; }
        else if(t==='ol'){ for(var k=0;k<c.children.length;k++){ out+=(k+1)+'. '+c.children[k].textContent+'\\n'; } out+='\\n'; }
        else if(t==='a'){ var href=c.getAttribute('href')||''; out+='['+inner+']('+href+')'; }
        else if(t==='img'){ var src=c.getAttribute('src')||''; var alt=c.getAttribute('alt')||''; out+='!['+alt+']('+src+')'; }
        else out+=inner;
      } return out;
    }
    return walk(div).trim();
  }

  function buildUI(ta){
    var brand = document.getElementById('qdck5-brand');
    var tools = document.createElement('div'); tools.className='qdck5-toolbar';
    var btnPrev = document.createElement('button'); btnPrev.className='qdck5-btn'; btnPrev.textContent=L.preview;
    var btnHtml = document.createElement('button'); btnHtml.className='qdck5-btn'; btnHtml.textContent=L.expHtml;
    var btnMd = document.createElement('button'); btnMd.className='qdck5-btn'; btnMd.textContent=L.expMd;
    var btnDark = document.createElement('button'); btnDark.className='qdck5-btn'; btnDark.textContent=L.dark;
    if(!cfg.enableExport){ btnHtml.style.display='none'; btnMd.style.display='none'; }
    if(!cfg.enableDarkToggle){ btnDark.style.display='none'; }
    tools.appendChild(btnPrev); tools.appendChild(btnHtml); tools.appendChild(btnMd); tools.appendChild(btnDark);
    brand.parentNode.insertBefore(tools, brand.nextSibling);

    var wrap = document.createElement('div'); wrap.className='qdck5-split';
    var edWrap = document.createElement('div'); edWrap.className='qdck5-editor-wrap';
    var divider = document.createElement('div'); divider.className='qdck5-divider'; divider.title='drag';
    var pvWrap = document.createElement('div'); pvWrap.className='qdck5-preview-wrap'; pvWrap.style.display = (cfg.enablePreviewPane?'block':'none');
    var pv = document.createElement('div'); pv.className='qdck5-preview'; pv.id='qdck5-preview'; pv.innerHTML='<em>预览...</em>';
    pvWrap.appendChild(pv);

    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(edWrap);
    wrap.appendChild(divider);
    wrap.appendChild(pvWrap);
    edWrap.appendChild(ta);

    pv.style.setProperty('--qdck5-preview-h', (cfg.height||720)+'px');

    var down=false,startX=0;
    divider.addEventListener('mousedown', function(e){ down=true; startX=e.clientX; document.body.style.userSelect='none'; });
    window.addEventListener('mousemove', function(e){
      if(!down) return;
      var dx = e.clientX - startX;
      var edRect = edWrap.getBoundingClientRect();
      var wrapRect = wrap.getBoundingClientRect();
      var newLeft = Math.min(Math.max(300, edRect.width + dx), wrapRect.width - 300);
      wrap.style.gridTemplateColumns = newLeft+'px 8px 1fr';
      startX = e.clientX;
    });
    window.addEventListener('mouseup', function(){ if(down){ down=false; document.body.style.userSelect=''; } });

    btnPrev.addEventListener('click', function(){
      pvWrap.style.display = (pvWrap.style.display==='none' ? 'block' : 'none');
    });
    btnHtml.addEventListener('click', function(){
      var html = getCurrentHTML();
      download('post.html', '<!doctype html><meta charset=\"utf-8\">'+html, 'text/html;charset=utf-8');
    });
    btnMd.addEventListener('click', function(){
      var md = htmlToMd(getCurrentHTML());
      download('post.md', md, 'text/markdown;charset=utf-8');
    });
    btnDark.addEventListener('click', function(){ document.body.classList.toggle('qdck5-dark'); });

    return {preview: pv, pvWrap: pvWrap};
  }

  function getCurrentHTML(){
    if(window.QDCK5 && QDCK5.editor){
      try{ return QDCK5.editor.getData(); }catch(e){}
    }
    var ta = findTextarea();
    return ta ? ta.value : '';
  }

  function tieSyncScroll(editor, ui){
    try{
      var edEl = editor.ui.view.editable.element;
      edEl.addEventListener('scroll', function(){
        if(!ui || !ui.preview) return;
        var e = edEl; var p = ui.preview;
        var ratio = e.scrollTop / (e.scrollHeight - e.clientHeight || 1);
        p.scrollTop = ratio * (p.scrollHeight - p.clientHeight);
      });
    }catch(e){}
  }

  function UploadAdapter(loader, up){
    this.loader = loader; this.up = up;
  }
  UploadAdapter.prototype.upload = function(){
    var up = this.up;
    return this.loader.file.then(function(file){
      return new Promise(function(resolve, reject){
        var xhr = new XMLHttpRequest();
        var url = up.url + (up.url.indexOf('?')>-1?'&':'?') + '__typecho_token=' + encodeURIComponent(up.token||'');
        xhr.open('POST', url, true);
        var fm = new FormData(); fm.append('upload', file);
        xhr.onload = function(){
          try{
            var res = JSON.parse(xhr.responseText||'{}');
            if(res && res.url){ resolve({ default: res.url }); }
            else { reject(res && res.error ? res.error.message : '上传失败'); }
          }catch(e){ reject('上传响应解析失败'); }
        };
        xhr.onerror = function(){ reject('网络错误'); };
        xhr.send(fm);
      });
    });
  };
  UploadAdapter.prototype.abort = function(){};

  function initCK(ta, ui){
    var tb = (cfg.toolbar && cfg.toolbar.length) ? cfg.toolbar : ['heading','bold','italic','underline','strikethrough','link','blockQuote','codeBlock','bulletedList','numberedList','insertTable','undo','redo'];
    var url = (cfg.useCDN && cfg.cdnUrl) ? cfg.cdnUrl : (cfg.baseUrl + '/assets/ckeditor5/classic/ckeditor.js');
    var s=document.createElement('script'); s.src=url; s.onload=function(){
      if(!window.ClassicEditor){
        console.warn('CKEditor5 loaded but ClassicEditor not found');
        return;
      }
      window.QDCK5 = window.QDCK5 || {};
      ClassicEditor.create(ta, { toolbar: tb, language: (cfg.uiLang||'zh-CN') }).then(function(editor){
        QDCK5.editor = editor;
        var h = (cfg.height||720)+'px';
        editor.ui.view.editable.element.style.minHeight = h;
        editor.ui.view.editable.element.style.maxHeight = h;
        editor.ui.view.editable.element.style.overflow = 'auto';

        if(cfg.upload && cfg.upload.url){
          editor.plugins.get('FileRepository').createUploadAdapter = function(loader){ return new UploadAdapter(loader, cfg.upload); };
        }

        function sync(){
          try{
            var html = editor.getData() || '';
            var el = findTextarea(); if(el){ el.value = html; }
            if(cfg.enablePreviewPane && ui && ui.preview){ ui.preview.innerHTML = html; }
          }catch(e){}
        }
        editor.model.document.on('change:data', function(){ if(cfg.autosyncOnInput){ sync(); } });
        sync();
        tieSyncScroll(editor, ui);

        document.addEventListener('click', function(e){
          var t=e.target; if(!t) return;
          if(t.type==='submit' || (t.id && (t.id.indexOf('advance-submit')!==-1 || t.id.indexOf('btn-preview')!==-1 || t.id.indexOf('btn-save')!==-1))){
            sync();
            if(cfg.autoLocalize){
              var html = editor.getData()||'';
              var urls = []; html.replace(/<img[^>]+src=["']([^"']+)["']/gi, function(_,u){ if(/^https?:\\/\\//i.test(u)) urls.push(u); });
              var uniq = Array.from(new Set(urls)).slice(0,10);
              uniq.forEach(function(u){
                try{
                  var xhr = new XMLHttpRequest();
                  var fu = cfg.fetch.url + (cfg.fetch.url.indexOf('?')>-1?'&':'?') + '__typecho_token=' + encodeURIComponent(cfg.fetch.token||'');
                  xhr.open('POST', fu, true);
                  xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
                  xhr.onload = function(){
                    try{
                      var r = JSON.parse(xhr.responseText||'{}');
                      if(r && r.ok && r.url){
                        var d = editor.getData();
                        editor.setData(d.split(u).join(r.url));
                      }
                    }catch(e){}
                  };
                  xhr.send('url='+encodeURIComponent(u));
                }catch(e){}
              });
            }
          }
        }, true);
        var form = ta.closest('form'); if(form){ form.addEventListener('submit', function(){ /* synced */ }, true); }

      }).catch(function(err){ console.error('CK5 init error', err); });
    };
    s.onerror=function(){ console.warn('CK5 加载失败'); };
    document.head.appendChild(s);
  }

  function boot(){
    var ta = findTextarea();
    if(!ta) return false;
    var ui = buildUI(ta);
    initCK(ta, ui);
    return true;
  }

  function start(){
    var tries=0, max=40;
    var timer=setInterval(function(){
      if(boot()){ clearInterval(timer); }
      tries++;
      if(tries>=max){ clearInterval(timer); }
    }, 200);
  }
  if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', start); }
  else { start(); }

})();