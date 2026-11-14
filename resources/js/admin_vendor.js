// Scaffolding
import 'admin-lte';

// Bootstrap
import 'bootstrap4';

// Jquery
import jQuery from 'jquery';

import 'jquery-datetimepicker';
// костыль для 2.5.21 (time picker mouse scroll)
jQuery.fn.xdsoftScroller = function (options, percent) {
  return this.each(function () {
    var timeboxparent = $(this),
      pointerEventToXY = function (e) {
        var out = {x: 0, y: 0},
          touch;
        if (e.type === 'touchstart' || e.type === 'touchmove' || e.type === 'touchend' || e.type === 'touchcancel') {
          touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
          out.x = touch.clientX;
          out.y = touch.clientY;
        } else if (
          e.type === 'mousedown' ||
          e.type === 'mouseup' ||
          e.type === 'mousemove' ||
          e.type === 'mouseover' ||
          e.type === 'mouseout' ||
          e.type === 'mouseenter' ||
          e.type === 'mouseleave'
        ) {
          out.x = e.clientX;
          out.y = e.clientY;
        }
        return out;
      },
      timebox,
      parentHeight,
      height,
      scrollbar,
      scroller,
      maximumOffset = 100,
      start = false,
      startY = 0,
      startTop = 0,
      h1 = 0,
      touchStart = false,
      startTopScroll = 0,
      calcOffset = function () {};

    if (percent === 'hide') {
      timeboxparent.find('.xdsoft_scrollbar').hide();
      return;
    }

    if (!$(this).hasClass('xdsoft_scroller_box')) {
      timebox = timeboxparent.children().eq(0);
      parentHeight = timeboxparent[0].clientHeight;
      height = timebox[0].offsetHeight;
      scrollbar = $('<div class="xdsoft_scrollbar"></div>');
      scroller = $('<div class="xdsoft_scroller"></div>');
      scrollbar.append(scroller);

      timeboxparent.addClass('xdsoft_scroller_box').append(scrollbar);
      calcOffset = function calcOffset(event) {
        var offset = pointerEventToXY(event).y - startY + startTopScroll;
        if (offset < 0) {
          offset = 0;
        }
        if (offset + scroller[0].offsetHeight > h1) {
          offset = h1 - scroller[0].offsetHeight;
        }
        timeboxparent.trigger('scroll_element.xdsoft_scroller', [maximumOffset ? offset / maximumOffset : 0]);
      };

      scroller
        .on('touchstart.xdsoft_scroller mousedown.xdsoft_scroller', function (event) {
          if (!parentHeight) {
            timeboxparent.trigger('resize_scroll.xdsoft_scroller', [percent]);
          }

          startY = pointerEventToXY(event).y;
          startTopScroll = parseInt(scroller.css('margin-top'), 10);
          h1 = scrollbar[0].offsetHeight;

          if (event.type === 'mousedown' || event.type === 'touchstart') {
            if (options.ownerDocument) {
              $(options.ownerDocument.body).addClass('xdsoft_noselect');
            }
            $([options.ownerDocument.body, options.contentWindow]).on(
              'touchend mouseup.xdsoft_scroller',
              function arguments_callee() {
                $([options.ownerDocument.body, options.contentWindow])
                  .off('touchend mouseup.xdsoft_scroller', arguments_callee)
                  .off('mousemove.xdsoft_scroller', calcOffset)
                  .removeClass('xdsoft_noselect');
              }
            );
            $(options.ownerDocument.body).on('mousemove.xdsoft_scroller', calcOffset);
          } else {
            touchStart = true;
            event.stopPropagation();
            event.preventDefault();
          }
        })
        .on('touchmove', function (event) {
          if (touchStart) {
            event.preventDefault();
            calcOffset(event);
          }
        })
        .on('touchend touchcancel', function () {
          touchStart = false;
          startTopScroll = 0;
        });

      timeboxparent
        .on('scroll_element.xdsoft_scroller', function (event, percentage) {
          if (!parentHeight) {
            timeboxparent.trigger('resize_scroll.xdsoft_scroller', [percentage, true]);
          }
          percentage = percentage > 1 ? 1 : percentage < 0 || isNaN(percentage) ? 0 : percentage;

          scroller.css('margin-top', maximumOffset * percentage);

          setTimeout(function () {
            timebox.css('marginTop', -parseInt((timebox[0].offsetHeight - parentHeight) * percentage, 10));
          }, 10);
        })
        .on('resize_scroll.xdsoft_scroller', function (event, percentage, noTriggerScroll) {
          var percent, sh;
          parentHeight = timeboxparent[0].clientHeight;
          height = timebox[0].offsetHeight;
          percent = parentHeight / height;
          sh = percent * scrollbar[0].offsetHeight;
          if (percent > 1) {
            scroller.hide();
          } else {
            scroller.show();
            scroller.css('height', parseInt(sh > 10 ? sh : 10, 10));
            maximumOffset = scrollbar[0].offsetHeight - scroller[0].offsetHeight;
            if (noTriggerScroll !== true) {
              timeboxparent.trigger('scroll_element.xdsoft_scroller', [
                percentage || Math.abs(parseInt(timebox.css('marginTop'), 10)) / (height - parentHeight),
              ]);
            }
          }
        });

      timeboxparent.on('mousewheel', function (event) {
        var top = Math.abs(parseInt(timebox.css('marginTop'), 10));

        top = top - event.originalEvent.deltaY * -0.5; // this line fixed
        if (top < 0) {
          top = 0;
        }

        timeboxparent.trigger('scroll_element.xdsoft_scroller', [top / (height - parentHeight)]);
        event.stopPropagation();
        return false;
      });

      timeboxparent.on('touchstart', function (event) {
        start = pointerEventToXY(event);
        startTop = Math.abs(parseInt(timebox.css('marginTop'), 10));
      });

      timeboxparent.on('touchmove', function (event) {
        if (start) {
          event.preventDefault();
          var coord = pointerEventToXY(event);
          timeboxparent.trigger('scroll_element.xdsoft_scroller', [
            (startTop - (coord.y - start.y)) / (height - parentHeight),
          ]);
        }
      });

      timeboxparent.on('touchend touchcancel', function () {
        start = false;
        startTop = 0;
      });
    }
    timeboxparent.trigger('resize_scroll.xdsoft_scroller', [percent]);
  });
};

import 'inputmask/dist/jquery.inputmask.min.js';
import 'bootstrap-datepicker/dist/js/bootstrap-datepicker.min';
//import 'summernote/dist/summernote-bs4';
import 'bootstrap4-toggle';

window.$ = window.jQuery = jQuery;
$.ajaxPrefilter(function (options, originalOptions, xhr) {
  var token = $('meta[name="csrf-token"]').attr('content');

  if (token) {
    return xhr.setRequestHeader('X-CSRF-TOKEN', token); // X-XSRF-TOKEN
  }
});

// Select2
import select2 from 'select2';
select2();

// Moment
import moment from 'moment';
window.moment = moment;

// Swal
import Swal from 'sweetalert2';
window.Swal = Swal;

// Toastr
import Toastr from 'toastr';
window.toastr = Toastr; // #public_bundle | https://cdnjs.com/libraries/toastr.js/2.1.4

// bsCustomFileInput
import bsCustomFileInput from 'bs-custom-file-input';
window.bsCustomFileInput = bsCustomFileInput;

// Axios
import axios from 'axios'; // X-XSRF-TOKEN for stateful api !!!
window.axios = axios; // #public_bundle | https://cdnjs.com/libraries/axios/1.6.8
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
//window.axios.defaults.withCredentials = true;
//window.axios.defaults.withXSRFToken = true;

// Chart.js
import Chart from 'chart.js';
window.Chart = Chart;

// Dropzone
//import Dropzone from 'dropzone';
//window.Dropzone = Dropzone;

// Vue
//import {createApp, ref} from 'vue';
//window.createApp = createApp;
//window.ref = ref;
