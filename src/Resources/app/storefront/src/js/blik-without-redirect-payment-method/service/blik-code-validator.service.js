const BLIK_CODE_REGEX = new RegExp('^[0-9][0-9][0-9]+\\s?[0-9][0-9][0-9]$');

export default class BlikCodeValidatorService {
    validate(blikCode) {
        if (blikCode.length === 0) {
            return false;
        }

        return BLIK_CODE_REGEX.test(blikCode);
    }
}
