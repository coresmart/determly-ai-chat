// Page integration for the support chat example.
// The injected widget sets window.determlyChat and turns WebMCP on.
// Register page tools before the first message. Persona snapshots them on each send.

function refreshOrders() {
  if (window.Livewire) {
    window.Livewire.dispatch('orders-refresh');
    return;
  }
  var node = document.querySelector('[data-orders]');
  if (node && window.axios) {
    window.axios.get('/orders').then(function (response) {
      node.innerHTML = response.data;
    });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  var modelContext = document.modelContext;
  if (!modelContext || typeof modelContext.registerTool !== 'function') {
    return;
  }
  modelContext.registerTool({
    name: 'refresh_orders',
    description: 'Reload the orders table on this page.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
    annotations: { readOnlyHint: true },
    execute: async function () {
      refreshOrders();
      return { content: [{ type: 'text', text: 'Orders refreshed.' }] };
    },
  });
});

window.addEventListener('persona:chat-ready', function (event) {
  var handle = event.detail;
  if (!handle || typeof handle.on !== 'function') {
    return;
  }
  handle.on('assistant:complete', function () {
    refreshOrders();
  });
  handle.on('run:finished', function (run) {
    if (run && run.outcome === 'error') {
      refreshOrders();
    }
  });
});
