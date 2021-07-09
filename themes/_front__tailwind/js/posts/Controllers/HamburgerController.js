import Controller from "../../js/Controller";
import {register} from '../../js/scripts';

export default register('hamburger',

    /**
     * @property {string} options.$sidebar
     * @property {string} options.opened_class
     * @property {string} options.closed_class
     */
    class HamburgerController extends Controller {
        get events() {
            return Object.assign({}, super.events, {
                'click': 'onClicked',
                'click document': 'onDocumentClicked',
            });
        }

        get sidebar_element() {
            return document.querySelector(this.options.$sidebar);
        }

        get is_sidebar_opened() {
            let classList = this.sidebar_element.classList;

            return classList.contains(this.options.opened_class);
        }

        openSidebar() {
            let classList = this.sidebar_element.classList;

            classList.remove(this.options.closed_class);
            classList.add(this.options.opened_class);
        }

        closeSidebar() {
            let classList = this.sidebar_element.classList;

            classList.remove(this.options.opened_class);
            classList.add(this.options.closed_class);
        }

        onClicked() {
            if (!this.is_sidebar_opened) {
                this.openSidebar();
            }
            else {
                this.closeSidebar();
            }
        }

        onDocumentClicked(e) {
            if (!this.is_sidebar_opened) {
                return;
            }

            if (this.sidebar_element.contains(e.target)) {
                return;
            }

            if (this.element.contains(e.target)) {
                return;
            }

            this.closeSidebar();
        }
    }
);