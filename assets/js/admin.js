/* Admin pages: show the value next to each Game DNA slider while dragging. */
'use strict';

document.addEventListener('input', (event) => {
  const slider = event.target.closest('input[type="range"][data-output]');
  if (slider) {
    document.getElementById(slider.dataset.output).textContent = slider.value;
  }
});
