/**
 * System initialization file for setting up global modules and configurations.
 */
import './general/index';
import './system/skeleton/index';
import './system/theme/index';
import './system/realtime/datasets';

// Log system initialization with fallback
document.addEventListener('DOMContentLoaded', () => {
  if (!window.general) {
    console.log('System initialized, but General class not loaded yet');
    return;
  }
  window.general.log('System initialized with Skeleton and General modules');
});


