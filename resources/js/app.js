import './bootstrap';

import Alpine from 'alpinejs';
import currencyMaskPlugin from './currency-mask';

Alpine.plugin(currencyMaskPlugin);

window.Alpine = Alpine;

Alpine.start();
