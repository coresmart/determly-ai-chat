// Registered chat components for Persona 4.39.0.
// DynamicForm, ProductCard, and Notice render inside the transcript or an artifact pane.
(function () {
window.DetermlyChatComponents = {
  DynamicForm: function (props) {
    var form = document.createElement('form');
    form.setAttribute('data-determly-component', 'DynamicForm');
    form.style.cssText = 'display:flex;flex-wrap:wrap;gap:12px;margin:0;';

    var title = document.createElement('div');
    title.textContent = text(props.title, 'Form');
    title.style.cssText = 'width:100%;font-weight:600;';
    form.appendChild(title);

    var fields = Array.isArray(props.fields) ? props.fields : [];
    fields.forEach(function (field) {
      if (!field || typeof field !== 'object') return;
      var wrap = document.createElement('label');
      wrap.style.cssText = 'display:flex;flex-direction:column;gap:4px;font-size:14px;' + (field.width === 'half' ? 'width:calc(50% - 6px);' : 'width:100%;');
      var name = text(field.label, 'Field');
      var heading = document.createElement('span');
      heading.textContent = name + (field.required ? ' *' : '');
      wrap.appendChild(heading);
      var control = field.type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
      if (control.tagName === 'INPUT') {
        control.type = ['email', 'tel', 'url', 'number', 'date', 'time'].indexOf(field.type) >= 0 ? field.type : 'text';
      } else {
        control.rows = 3;
      }
      control.name = text(field.name, name.toLowerCase().replace(/[^a-z0-9]+/g, '_'));
      control.required = field.required === true;
      control.style.cssText = 'font:inherit;padding:8px;border:1px solid #cbd5e1;border-radius:8px;';
      wrap.appendChild(control);
      if (field.helper_text) {
        var help = document.createElement('span');
        help.textContent = String(field.helper_text);
        help.style.cssText = 'color:#64748b;font-size:12px;';
        wrap.appendChild(help);
      }
      form.appendChild(wrap);
    });

    var submit = document.createElement('button');
    submit.type = 'submit';
    submit.textContent = text(props.submit_text, 'Send');
    submit.style.cssText = 'font:inherit;padding:8px 14px;border:0;border-radius:8px;background:#0f172a;color:#fff;cursor:pointer;';
    form.appendChild(submit);

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;
      var parts = [];
      Array.prototype.forEach.call(form.elements, function (element) {
        if (!element.name || element.type === 'submit') return;
        parts.push(element.name + ': ' + element.value);
      });
      sendToChat(text(props.title, 'Form') + '. ' + parts.join('. '));
      submit.disabled = true;
      submit.textContent = 'Sent';
    });

    return form;
  },

  ProductCard: function (props) {
    var card = document.createElement('div');
    card.setAttribute('data-determly-component', 'ProductCard');
    card.style.cssText = 'display:flex;flex-direction:column;gap:8px;';
    var title = document.createElement('div');
    title.textContent = text(props.title, 'Product');
    title.style.cssText = 'font-weight:600;';
    var price = document.createElement('div');
    price.textContent = text(props.price, '');
    var body = document.createElement('div');
    body.textContent = text(props.description, '');
    var button = document.createElement('button');
    button.type = 'button';
    button.textContent = text(props.button, 'Add to cart');
    button.style.cssText = 'font:inherit;align-self:flex-start;padding:8px 14px;border:0;border-radius:8px;background:#0f172a;color:#fff;cursor:pointer;';
    button.addEventListener('click', function () {
      button.disabled = true;
      button.textContent = 'Added';
      sendToChat('Add ' + text(props.title, 'this product') + ' to the cart.');
    });
    card.append(title, price, body, button);
    return card;
  },

  Notice: function (props) {
    var notice = document.createElement('div');
    notice.setAttribute('data-determly-component', 'Notice');
    var tone = props.tone === 'success' || props.tone === 'warning' ? props.tone : 'info';
    var color = tone === 'success' ? '#166534' : (tone === 'warning' ? '#92400e' : '#1e3a8a');
    notice.style.cssText = 'display:flex;flex-direction:column;gap:4px;padding:12px;border-radius:8px;border:1px solid ' + color + ';';
    var title = document.createElement('div');
    title.textContent = text(props.title, 'Notice');
    title.style.cssText = 'font-weight:600;color:' + color + ';';
    var body = document.createElement('div');
    body.textContent = text(props.body, '');
    notice.append(title, body);
    return notice;
  },
};

function text(value, fallback) {
  return typeof value === 'string' && value.trim() !== '' ? value : fallback;
}

function sendToChat(message) {
  var key = window.siteAgentConfig && window.siteAgentConfig.windowKey;
  var handle = key ? window[key] : null;
  if (handle && typeof handle.sendMessage === 'function') {
    handle.sendMessage(message);
  }
}

// Plain replies stay plain. A JSON directive {"text","component","props"} shows the text and renders the component.
window.DetermlyChatStreamParser = function () {
  var latest = null;
  function extract(accumulated) {
    if (typeof accumulated !== 'string') return accumulated;
    var trimmed = accumulated.trim();
    var directive = trimmed.charAt(0) === '{' && (trimmed.indexOf('"component"') !== -1 || trimmed.indexOf('"text"') !== -1);
    if (!directive) {
      latest = accumulated;
      return accumulated;
    }
    try {
      var parsed = JSON.parse(trimmed);
      if (parsed && typeof parsed.text === 'string') {
        latest = parsed.text;
        return parsed.text;
      }
    } catch (error) {}
    var match = trimmed.match(/"text"\s*:\s*"((?:\\.|[^"\\])*)/);
    if (match) {
      try {
        latest = JSON.parse('"' + match[1] + '"');
      } catch (error) {
        latest = match[1];
      }
      return latest;
    }
    latest = '';
    return '';
  }
  return {
    processChunk: function (accumulated) { return extract(accumulated); },
    getExtractedText: function () { return latest; }
  };
};
})();
