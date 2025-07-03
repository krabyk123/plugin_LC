jQuery(function($){
  var select = $('#lc-lesson-select');

  // При смене урока — подгружаем данные
  select.on('change', function(){
    var id = $(this).val();
    if(!id) return;
    $.post(LC_Editor.ajax_url, {
      action      : 'lc_get_lesson_details',
      lesson_id   : id,
      _ajax_nonce : LC_Editor.nonce
    }, function(res){
      if(!res.success) return alert(res.data);
      var d = res.data;
      // Title
      if ( typeof tinymce !== 'undefined' ) {
        tinymce.get('edit_lesson_title_editor').setContent(d.lesson_title);
      }
      // Description
      if ( typeof tinymce !== 'undefined' ) {
        tinymce.get('edit_lesson_description_editor').setContent(d.lesson_description);
      }
      // Dates
      $('#edit_start').val(d.start.replace(' ','T'));
      $('#edit_end').val(d.end.replace(' ','T'));
      // Materials
      $('#edit_link1').val(d.materials.link1||'');
      $('#edit_link2').val(d.materials.link2||'');
      $('#edit_link3').val(d.materials.link3||'');
    },'json');
  });

  // При сабмите — сохраняем изменения
  $('#lc-update-lesson-form').on('submit', function(e){
    e.preventDefault();
    var id = select.val();
    if(!id) return alert('Выберите урок.');

    // Собираем поля
    var title = (tinymce&&tinymce.get('edit_lesson_title_editor'))
                ? tinymce.get('edit_lesson_title_editor').getContent()
                : $('textarea[name="lesson_title"]').val();
    var desc = (tinymce&&tinymce.get('edit_lesson_description_editor'))
                ? tinymce.get('edit_lesson_description_editor').getContent()
                : $('textarea[name="lesson_description"]').val();
    var start = $('#edit_start').val();
    var end   = $('#edit_end').val();
    var mat1  = $('#edit_link1').val(),
        mat2  = $('#edit_link2').val(),
        mat3  = $('#edit_link3').val();

    // AJAX-обновление
    $.post(LC_Editor.ajax_url, {
      action      : 'lc_update_lesson',
      lesson_id   : id,
      lesson_title: title,
      lesson_description: desc,
      start       : start,
      end         : end,
      mat: { link1: mat1, link2: mat2, link3: mat3 },
      _ajax_nonce : LC_Editor.nonce
    }, function(res){
      if(res.success){
        alert('Урок обновлён');
        // Обновить текст опции в select
        var opt = select.find('option[value="'+id+'"]'),
            date = res.data.start.split(' ')[0];
        opt.text(res.data.lesson_title+' — '+date);
      } else {
        alert('Ошибка: '+res.data);
      }
    }, 'json');
  });
});
