import Plugin from 'src/plugin-system/plugin.class';

const BLIK_FORMAT_REGEX = [
    new RegExp('^[0-9]$'),
    new RegExp('^[0-9][0-9]$'),
    new RegExp('^[0-9][0-9][0-9]$'),
    new RegExp('^[0-9][0-9][0-9]+\\s$'),
    new RegExp('^[0-9][0-9][0-9]+\\s[0-9]$'),
    new RegExp('^[0-9][0-9][0-9]+\\s[0-9][0-9]$'),
    new RegExp('^[0-9][0-9][0-9]+\\s[0-9][0-9][0-9]$')
];

export default class BlikCodeFormatterPlugin extends Plugin {
    init() {
        this.validate();
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('keyup', this.validate.bind(this));
        this.el.addEventListener('paste', this.validate.bind(this));
    }

    validate() {
        const
            insertAt = (str, sub, pos) => `${str.slice(0, pos)}${sub}${str.slice(pos)}`,
            isSpaceInserted = this.el.value[3] === ' ';
        let l = this.el.value.length;

        if (l < 0) {
            this.el.value = '';
        }

        if (l <= 7 && l > 0) {
            if (l >= 4 && !isSpaceInserted) {
                this.el.value = insertAt(this.el.value, ' ', 3);

                this.validate();

                return;
            }

            while (l > 0 && !BLIK_FORMAT_REGEX[l - 1].test(this.el.value)) {
                this.el.value = this.el.value.slice(0, -1)
                l = this.el.value.length;
            }
        } else if (l >= 8 && l <= 9) {
            this.el.value = this.el.value.slice(0, -1);
        }

        if (this.el.value.length >= 8) {
            this.el.value = this.el.value.slice(0, 7 - this.el.value.length);
        }
    }
}
