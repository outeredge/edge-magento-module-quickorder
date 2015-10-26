var counter = 1;
function addInput(divName){
    var newdiv = document.createElement('div');
    newdiv.setAttribute("id", "input" + counter);
    newdiv.innerHTML = "Name / Sku: <input type='text' name='product[" + counter + "][sku]'>\n\
                        Qty: <input type='text' name='product[" + counter + "][qty]'>";
    document.getElementById(divName).appendChild(newdiv);
    counter++;

}