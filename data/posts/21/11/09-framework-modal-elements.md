# Modal Elements

When active, modal elements - dialogs, pickers, or AJAX spinners - need to prevent user interaction with the rest of the page. 

A common approach is putting an overlay `<div>` under the modal element covering the rest of the page, as a click shield. However, user can still navigate the page with the keyboard.

Today, I implemented a better solution by capturing mouse and focus events outside the modal element, and keeping focus inside.

More details:

{{ toc }}

### meta.abstract

When active, modal elements - dialogs, pickers, or AJAX spinners - need to prevent user interaction with the rest of the page.

A common approach is putting an overlay `<div>` under the modal element covering the rest of the page, as a click shield. However, user can still navigate the page with the keyboard.

Today, I implemented a better solution by capturing mouse and keyboard events outside the modal element, and keeping focus inside.

## Example

A [form](https://github.com/osmphp/admin/blob/HEAD/themes/_admin__tailwind/js/forms/Controllers/Form.js) I'm working on saves its data using an AJAX request. 

While waiting for response, it shows a `Saving new scope ...` message. In order to prevent user interaction with the rest of the page I call:

    import {capture, release} from '../js/scripts';
    ...
    capture(this.message_bar_element);

After receiving a response, I enable the interaction back by calling

    release();
    
## How It Works

While dispatching an event, the browser notifies all the parent elements starting from the `document` till the target element (the *capture* phase), then the target element itself, and then all the same elements ending with `document` (the *bubbling* phase):

![](https://javascript.info/article/bubbling-and-capturing/eventflow.svg)

Between `capture()` and `release()` calls, I listen to all mouse events on the `document` during the capture phase, that is, very early, and if they happen outside the current modal element, prevent further dispatch. 

It's implemented in the [`Capturing`](https://github.com/osmphp/framework/blob/HEAD/themes/_base/js/js/Capturing.js) class:

    export default class Capturing {
        ...
        events = {
            'mousedown': 'onMouseDown',
            'mouseup': 'onMouseUp',
            'click': 'onClick',
            'dblclick': 'onDoubleClick',
            'focus': 'onFocus',
        };
        ...
        listen() {
            for (let type in this.events) {
                if (!this.events.hasOwnProperty(type)) {
                    continue;
                }
    
                document.addEventListener(type, e => {
                    if (this.outside(e)) {
                        this[this.events[type]](e);
                    }
                }, true);
            }
    
        }
    
        outside(e) {
            return this.capturing_element &&
                !this.capturing_element.contains(e.target);
        }
    
        onMouseDown(e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
        }

        ...    
    }   
    
Focusing an element using the `Tab` key can't be canceled. However, you return the focus to the modal element:

    onFocus(e) {
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (document.activeElement &&
            document.activeElement instanceof HTMLElement)
        {
            document.activeElement.blur();
        }
        this.capturing_element.focus();
    }

Finally, some modal elements may want to close if the user clicks outside, so I notify them by sending a custom event:

    onClick(e) {
        e.stopPropagation();
        e.stopImmediatePropagation();
        this.capturing_element.dispatchEvent(new CustomEvent('outside-click', {
            detail: {e}
        }));
    }

## Stacked Modals

It's worth mentioning that using this approach, you can stack a modal element on top of other modal elements, capture user's input recursively:

    // show a modal dialog, and capture page events
    capture(dialogElement);    
    ...
        // in the dialog, open a color picker, and capture 
        // both page and dialog events     
        capture(colorPickerElement);    

        // after closing the color picker, release the capture back 
        // to the dialog
        release();
        
    // after closing the dialog, stop capturing
    release(); 