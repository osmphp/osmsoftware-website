import Controller from "../../js/Controller";
import {register} from '../../js/scripts';

export default register('open-option-value',

    class OpenOptionValue extends Controller {
        get events() {
            return Object.assign({}, super.events, {
                'change': 'onChanged',
            });
        }

        onChanged() {
            if (this.element.value) {
                location.href = this.element.value;
            }
        }
    }
);