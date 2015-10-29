var counter = 0;

function addInput(sku) {
    var divName = 'itemInput';
    var newdiv = document.createElement('div');
    var inputId = "input" + counter;
    newdiv.setAttribute("id", inputId);
    newdiv.innerHTML = "Sku: <input type='text' class='input-sku' name='product[" + counter + "][sku]' value='" + sku + "'>\n\
                        Qty: <input type='text' class='input-qty' name='product[" + counter + "][qty]'>\n\
                        <input class='button-remove' type='button' value='Remove' onClick='removeInput(" + inputId + ");'>";
    document.getElementById(divName).appendChild(newdiv);
    counter++;
}

function removeInput(inputId) {
    $(inputId).remove();
}

/**
 * Quick Search
 */
Varien.searchFormQuickOrder = Class.create();
Varien.searchFormQuickOrder.prototype = {
    initialize : function(form, field, emptyText){
        this.form   = $(form);
        this.field  = $(field);
        this.emptyText = emptyText;

        Event.observe(this.form,  'submit', this.submit.bind(this));
        Event.observe(this.field, 'focus', this.focus.bind(this));
        Event.observe(this.field, 'blur', this.blur.bind(this));
        this.blur();
    },

    submit : function(event){
        if (this.field.value == this.emptyText || this.field.value == ''){
            Event.stop(event);
            return false;
        }
        return true;
    },

    focus : function(event){
        if(this.field.value==this.emptyText){
            this.field.value='';
        }

    },

    blur : function(event){
        if(this.field.value==''){
            this.field.value=this.emptyText;
        }
    },

    initAutocomplete : function(url, destinationElement){
        new Ajax.Autocompleter(
            this.field,
            destinationElement,
            url,
            {
                paramName: this.field.name,
                method: 'get',
                minChars: 2,
                updateElement: this._selectAutocompleteItem.bind(this),
                onShow : function(element, update) {
                    if(!update.style.position || update.style.position=='absolute') {
                        update.style.position = 'absolute';
                        Position.clone(element, update, {
                            setHeight: false,
                            offsetTop: element.offsetHeight
                        });
                    }
                    Effect.Appear(update,{duration:0});
                }

            }
        );
    },

    _selectAutocompleteItem : function(element){
        if(element.title){
            formId = addInput(element.title);
        }
    }
}