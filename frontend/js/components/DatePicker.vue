<template>
  <a17-inputframe :name="name" :error="error" :note="note" :label="label" :label-for="uniqId" class="datePicker"
                  :class="{ 'datePicker--static' : staticMode, 'datePicker--mobile' : isMobile }" :required="required">
    <div class="datePicker__group" ref="flatPicker">
      <div class="form__field datePicker__field">
        <input type="text" :name="name" :id="uniqId" :required="required" :placeholder="placeHolder" data-input
               v-model="date" :disabled="disabled">
        <a href="#" v-if="clear" class="datePicker__reset" :class="{ 'datePicker__reset--cleared' : !date }"
           @click.prevent="onClear"><span v-svg symbol="close_icon"></span></a>
      </div>
    </div>
  </a17-inputframe>
</template>

<script>
  import 'flatpickr/dist/flatpickr.css'

  import parse from 'date-fns/parse'
  import FlatPickr from 'flatpickr'

  import FormStoreMixin from '@/mixins/formStore'
  import InputframeMixin from '@/mixins/inputFrame'
  import randKeyMixin from '@/mixins/randKey'
  import { getCurrentLocale, isCurrentLocale24HrFormatted, locales } from '@/utils/locale'

  export default {
    name: 'A17DatePicker',
    mixins: [randKeyMixin, InputframeMixin, FormStoreMixin],
    props: {
      /* @see: https://chmln.github.io/flatpickr/options/ */
      name: { // FlatPicker hidden input name
        type: String,
        default: 'date'
      },
      required: {
        type: Boolean,
        default: false
      },
      placeHolder: {
        type: String,
        default: ''
      },
      allowInput: {
        type: Boolean,
        default: false
      },
      enableTime: {
        type: Boolean,
        default: false
      },
      noCalendar: {
        type: Boolean,
        default: false
      },
      time_24hr: {
        type: Boolean,
        default: isCurrentLocale24HrFormatted()
      },
      altFormat: {
        type: String,
        default: null
      },
      inline: {
        type: Boolean,
        default: false
      },
      initialValue: {
        type: String,
        default: null
      },
      hourIncrement: {
        type: Number,
        default: 1
      },
      minuteIncrement: {
        type: Number,
        default: 30
      },
      staticMode: { // Set static when the input need to show inside a sticky element (in the publish module for example)
        type: Boolean,
        default: false
      },
      minDate: {
        type: String,
        default: null
      },
      maxDate: {
        type: String,
        default: null
      },
      disabled: {
        type: Boolean,
        default: false
      },
      mode: {
        type: String,
        default: 'single',
        validator: function (value) {
          return value === 'single' || value === 'multiple' || value === 'range'
        }
      },
      clear: {
        type: Boolean,
        default: false
      }
    },
    data: function () {
      return {
        date: this.initialValue,
        isMobile: false,
        flatPicker: null,
      }
    },
    computed: {
      uniqId: function () {
        return this.name + '-' + this.randKey
      },
      altFormatComputed: function () {
        if (this.altFormat !== null) {
          return this.altFormat
        }
        return 'F j, Y' + (this.enableTime ? (this.time_24hr || isCurrentLocale24HrFormatted() ? ' H:i' : ' h:i K') : '')
      }
    },
    methods: {
      config: function () {
        const config = {
          wrap: true,
          altInput: true,
          altFormat: this.altFormatComputed,
          dateFormat: (this.enableTime && this.noCalendar) ? 'H:i:S' : (this.enableTime ? 'Z' : 'Y-m-d'), // This is the universal format that will be parsed by the back-end.
          static: this.staticMode,
          appendTo: this.staticMode ? this.$refs.flatPicker : undefined,
          enableTime: this.enableTime,
          noCalendar: this.noCalendar,
          time_24hr: this.time_24hr,
          inline: this.inline,
          allowInput: this.allowInput,
          mode: this.mode,
          minuteIncrement: this.minuteIncrement,
          hourIncrement: this.hourIncrement,
          minDate: this.minDate,
          altInputClass: 'flatpickr-input form-control',
          maxDate: this.maxDate,
          parseDate: (date) => {
            if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(date)) {
              const fullFormat = 'yyyy-MM-dd HH:mm:ss';
              return parse(date + 'Z', fullFormat + 'X', Date.UTC());
            }
            if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
              const fullFormatNoTime = 'yyyy-MM-dd';
              return parse(date, fullFormatNoTime, Date.UTC());
            }

            if (this.isValidTime(date)) {
              const currentDate = new Date();
              date = `${currentDate.toDateString()} ${date}`;
            }

            // Hope for the best..
            return new Date(date.normalize("NFD").replace(/[\u0300-\u036f]/g, ""));
          },
          onOpen: () => {
            setTimeout(() => {
              this.flatPicker.set('maxDate', this.maxDate) // in case maxDate changed since last open
              this.flatPicker.set('minDate', this.minDate) // in case minDate changed since last open
              this.$emit('open', this.date)
            }, 10)

          },
          onClose: (_, dateStr) => {
            this.$nextTick(() => { // wait for the datepicker to properly update the UI
              this.$emit('close', this.date)
              if (!this.disabled) {
                this.date = dateStr;
                this.onInput();
              }
            })
          },
          onChange: () => {
            this.$nextTick(() => this.onInput())
          }
        }

        const locale = locales[getCurrentLocale()]

        if (locale !== undefined && locale.hasOwnProperty('flatpickr')) {
          config.locale = locale.flatpickr
        }

        return config
      },
      updateFromStore: function (newValue) { // called from the formStore mixin
        if (newValue !== this.date) {
          this.date = newValue
          this.flatPicker.setDate(newValue)
        }
      },
      onInput: function () {
        // see formStore mixin
        this.saveIntoStore()
        this.$emit('input', this.date)
      },
      onClear: function () {
        this.flatPicker.clear()
        this.onInput()
      },
      isValidTime: function (string) {
        const timeRegex = /^(0?[1-9]|1[0-2]):[0-5][0-9](?: (AM|PM))?$/i;
        const time24HrRegex = /^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/;
        return timeRegex.test(string) || time24HrRegex.test(string);
      }
    },
    mounted: function () {
      const el = this.$refs.flatPicker
      const opts = this.config()
      this.flatPicker = new FlatPickr(el, opts)

      this.isMobile = this.flatPicker.isMobile
    },
    beforeDestroy: function () {
      this.flatPicker.destroy()
    }
  }
</script>

<style lang="scss" scoped>

  .datePicker__field {
    display: flex;
  }

  .datePicker__reset {
    $button-reset__width: 45px - 13px - 14px;
    display: block;
    width: $button-reset__width;
    flex: 0 0 $button-reset__width;
    height: $button-reset__width;
    overflow: hidden;
    color: $color__background;
    background: $color__icons;
    border-radius: #{calc($button-reset__width / 2)};
    margin-top: 13px;
    margin-right: 13px;
    line-height: $button-reset__width;
    text-align: center;
    transition: opacity 0.2s ease;

    .icon {
      overflow: hidden;
      vertical-align: top;
      position: relative;
      top: 4px;
    }

    &:hover,
    &:focus {
      background: $color__fborder--active;
    }
  }

  .datePicker__reset.datePicker__reset--cleared {
    opacity: 0;
    pointer-events: none;
  }

  /* Static variant (but not in the mobile version) */
  .datePicker--static:not(.datePicker--mobile) {
    .form__field {
      height: 0;
      position: static;
      overflow: visible;
      border: 0 none;
    }

    .datePicker__reset {
      position: absolute;
      right: 0;
      top: 0;
    }
  }

  .flatpickr-wrapper {
    display: block;
  }
</style>

<style lang="scss">
  /* Mobile version */
  .datePicker__group input.flatpickr-input.flatpickr-mobile {
    width: 100%;
    font-family: inherit;
    font-size: inherit;
    background: transparent;
    border: 0 none;
    padding: 0 15px;
    -webkit-appearance: none;

    &::-webkit-clear-button {
      display: none;
    }

    &::-webkit-inner-spin-button {
      display: none;
    }

    &::-webkit-calendar-picker-indicator {
      display: none;
    }
  }
</style>
