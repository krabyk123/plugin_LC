jQuery(function($){
  // Показать поля ввода в режиме add/remove
  function showInput(action) {
    $('#teacher-balance .action-cell').empty();
    $('#teacher-balance').find('tr').each(function(){
      var $tr = $(this),
          userId = $tr.data('user-id'),
          cell = $tr.find('.action-cell');
      cell.append(
        '<div class="' + action + '-mode balance-mode" data-user-id="' + userId + '">' +
          '<input type="number" min="1" class="balance-input" placeholder="число">' +
          '<button class="balance-go">✓</button>' +
        '</div>'
      );
    });
  }

  $('#balance-add-btn').on('click', function(){
    showInput('add');
  });
  $('#balance-remove-btn').on('click', function(){
    showInput('remove');
  });

  // Обработка клика по ✓
  $('#teacher-balance').on('click', '.balance-go', function(){
    var $mode = $(this).closest('.balance-mode'),
        userId = $mode.data('user-id'),
        delta  = parseInt($mode.find('.balance-input').val(), 10);
    if (!delta || delta < 1) return;
    var type = $mode.hasClass('remove-mode') ? 'remove' : 'add';

    $.post(LC_AJAX.url, {
      action      : 'balance_update',
      _ajax_nonce : LC_AJAX.nonce,
      user_id     : userId,
      delta       : delta,
      type        : type
    }, function(res){
      if (res.success) {
        $('tr[data-user-id='+userId+'] .available').text(res.data.available);
      }
    });
  });
});
