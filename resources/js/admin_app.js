// i18n
import 'select2/dist/js/i18n/ru';
//import 'summernote/dist/lang/summernote-ru-RU';
import 'bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min';

// Widgets
import {Scaffolding} from './admin/scaffolding.js'; // #public_bundle

window.scaffolding = new Scaffolding([
  'SubmitReEnableWidget', // #public_bundle

  'DisableAllAlwaysWidget',
  'DisableIfEmptyWidget',
  'NullIfDisabledWidget',
  'NullIfEmptyWidget',

  'NumberMultipleWidget',
  //'OriginalValueWidget',

  'CustomFileInputWidget',
  'DatetimePickerWidget',
  'MaskWidget',
  'Select2Widget',
  //'SummernoteWidget',
  'SwalWidget',
  'DataToggleWidget',
]);

import './admin/widgets/custom-file-input.js';
import './admin/widgets/data-toggle.js';
import './admin/widgets/datetime-picker.js';
import './admin/widgets/disable-all-always.js';
import './admin/widgets/disable-if-empty.js';
import './admin/widgets/null-if-disabled.js';
import './admin/widgets/null-if-empty.js';
import './admin/widgets/number-multiple.js';
//import './admin/widgets/original-value.js';
import './admin/widgets/mask.js';
import './admin/widgets/select2.js';
import './admin/widgets/submit-re-enable.js'; // #public_bundle
//import './admin/widgets/summernote.js';
import './admin/widgets/swal.js';

// Features
import './admin/features/ajax-form'; // #public_bundle
import './admin/features/check-all';
import './admin/features/confirm-close';
import './admin/features/dynamic-name';
import './admin/features/filters-reset';
import './admin/features/focus';
import './admin/features/form';
import './admin/features/form-disable-hide';
import './admin/features/modals';
import './admin/features/render';
import './admin/features/select-all';
import './admin/features/table-empty';
import './admin/features/table-search';
import './admin/features/toastr'; // #public_bundle
import './admin/features/tr-disable';
import './admin/features/tr-duplicate';
import './admin/features/tr-move';
import './admin/features/tr-remove';
//import './admin/features/vue';

// Layout
import './admin/layout';
