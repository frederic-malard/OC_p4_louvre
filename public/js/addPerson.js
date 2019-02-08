$index = -1;

$persons = $('#reservation_persons').children();

if ($persons.length > 0)
{
    $last = $persons[$persons.length - 1];
    $littleForm = $last.children()[0];
    $index = parseInt($littleForm.id.substring(20));
}

$('#addPerson').click(function(){
    $index++;
    $('#reservation_persons').append($('#reservation_persons').data('prototype').replace(/__name__/g, $index));
    handleDeleteButtons();
});

function handleDeleteButtons()
{
    $('button[data-action="delete"]').click(function(){
        target = this.dataset.target;
        $(target).remove();
    });
}

handleDeleteButtons();