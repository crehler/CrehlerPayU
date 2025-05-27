import Iterator from 'src/helper/iterator.helper';

export default class TosValidatorService {
    constructor() {
        this.$isInvalidClass = 'is-invalid';
        this.$allRequiredTosPositions =
            document.querySelectorAll('.checkout-confirm-tos-checkbox[required="required"]');

        Iterator.iterate(this.$allRequiredTosPositions, (tos) => {
            tos.addEventListener('change', () => {
                tos.checked === false
                    ? tos.classList.add(this.$isInvalidClass)
                    : tos.classList.remove(this.$isInvalidClass);
            });
            tos.addEventListener('invalid', e => {
                e.target.scrollIntoView({behavior: 'smooth', block: 'center'})
            });
        })
    }

    validate() {
        if (this.$allRequiredTosPositions.length === 0) {
            return true;
        }

        Iterator.iterate(this.$allRequiredTosPositions, (tos) => {
            tos.checked === false
                ? tos.classList.add(this.$isInvalidClass)
                : tos.classList.remove(this.$isInvalidClass);
        })

        let invalidTosCount = 0;

        [].forEach.call(this.$allRequiredTosPositions, (tos) => {
            if (tos.classList.contains(this.$isInvalidClass)) {
                invalidTosCount++;
            }
        });

        return invalidTosCount === 0;
    }
}
