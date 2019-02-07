$index = 0;

$persons = $('#reservation_persons').children();

if ($persons.length > 0)
{
    $index = parseInt($persons[$persons.length - 2].id.substring(20));
}

$('#addPerson').click(function(){
    $index++;
    $('#reservation_persons').append($('#reservation_persons').data('prototype').replace(/__name__/g, $index));
});