function insertTag(e, startTag, endTag)
{
    var seekField = e;
    while (!seekField.classList.contains('formatbut')) {
        seekField = seekField.parentNode;
    }
    while (!seekField.tagName || seekField.tagName != 'TEXTAREA') {
        seekField = seekField.nextSibling;
    }
    var field = seekField;
    var scroll = field.scrollTop;
    field.focus();
    var startSelection   = field.value.substring(0, field.selectionStart);
    var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
    var endSelection     = field.value.substring(field.selectionEnd);
    if (currentSelection == "") {
        currentSelection = "TEXT";
    }
    field.value = startSelection + startTag + currentSelection + endTag + endSelection;
    field.focus();
    field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
    field.scrollTop = scroll;
}
function reply(code)
{
    var field = document.getElementById('form-commentaire').getElementsByTagName('textarea')[0];
    field.focus();
    if (field.value !== '') {
        field.value += '\n\n';
    }
    field.value += code;
    field.scrollTop = 10000;
    field.focus();
}
function displayMenu(e)
{
    var button = e.target;
    var menu = document.getElementById('sidenav');
    button.classList.toggle('active');
    menu.classList.toggle('shown');
}

if (document.getElementById('erreurs')) {
    window.location.hash = 'erreurs';
    window.scrollBy(0, -100);
}
