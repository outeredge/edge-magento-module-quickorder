var counter = 0;

function addInput(data) {
    var divName = 'itemInput';
    var newdiv  = document.createElement('div');
    var inputId = "input" + counter;

    newdiv.setAttribute("id", inputId);
    var html = "Sku: <input type='text' class='input-sku' name='product[" + counter + "][sku]' value='" + data.sku + "'>\
                Qty: <input type='text' class='input-qty' name='product[" + counter + "][qty]'>";

    for (var optionRow in data.opt) {
        if (data.opt) {
            var options = data.opt[optionRow];
            html += "<select name='product[" + counter + "][super_attribute][" + optionRow + "]'>";
                for (var row in options) {
                html += "<option value="+ row +" >"+options[row]+"</option>";
            }
            html += "</select>";
        }
    }

    html += "<input class='button-remove' type='button' value='Remove' onClick='removeInput(" + inputId + ");'>";

    if (data.extra) {
        html += "<div class='extra_delivery_quick_order'>\
                    <div><span>"+data.extra.extra_delivery_charges+" <a href="+data.extra.url_tc_extra_delivery+" target='_blank'>"+Translator.translate('Terms & Conditions').stripTags()+"</a></span></div>\
                    <div class='green-txt'>\n\
                        <input type='checkbox' class='extra_delivery_checkbox'>\n\
                        <span>"+data.extra.tc_for_extra_delivery+"</span>\n\
                    </div>\
                </div>";
    }

    document.getElementById(divName).appendChild(newdiv);
    newdiv.innerHTML = html;
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

    submit : function(event) {
        $extra = document.getElementsByClassName('extra_delivery_checkbox');
        if ($extra.length > 0) {
            for (var i = 0; i < $extra.length; ++i) {
                if (!$extra[i].checked) {
                    alert(Translator.translate('Please accept Terms & Conditions').stripTags());
                    Event.stop(event);
                    return false;
                }
            }
        }

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
            formId = addInput(JSON.parse(element.title));
        }
    }
}