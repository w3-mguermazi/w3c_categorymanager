import $ from 'jquery';
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Icons from '@typo3/backend/icons.js';
import Notification from "@typo3/backend/notification.js";

const getIcon = (identifier, size = Icons.sizes.small) => {
  return Icons.getIcon(identifier, size);
};

function handleToggle() {
  const $details = $(this);
  const uid = $details.data('uid');
  const state = this.open ? 1 : 0;

  const data = { uid: uid, state: state };
  const request = new AjaxRequest(TYPO3.settings.ajaxUrls['w3c_categorymanager_toggle_expand']);

  let promise = request.post(data);

  promise.then(async function (response) {
    const resolved = await response.resolve();
    if (!resolved.success) {
      Notification.error('Error', 'Expand state not saved', 0.8);
    }
  }, function (error) {
    Notification.error('Error', error.message, 0.8);
  });
}

$(function () {
  const $wrapper = $('.w3c_categorymanager');
  const $buttons = $('.toggle-category');
  const $filter = $('#w3c_categorymanager_filter');
  const $moveButtons = $('.move-category');
  const $expandedDefaults = $wrapper.find('details[data-expanded="1"]');

  if (!$wrapper.length || !$buttons.length) return;

  $buttons.each(function () {
    const $button = $(this);
    const uid = $button.data('uid');
    let hidden = $button.data('state');
    const iconIdentifier = hidden ? 'actions-toggle-off' : 'actions-toggle-on';

    getIcon(iconIdentifier).then(iconMarkup => {
      $button.empty().append(iconMarkup);
    });

    /**
     * Toggle category visibility
     */
    $button.on('click', function () {
      hidden = $button.data('state');
      const data = { uid: uid, hidden: hidden ^ 1 };
      const request = new AjaxRequest(TYPO3.settings.ajaxUrls['w3c_categorymanager_toggle_hide']);

      let promise = request.post(data);

      promise.then(async function (response) {
        const resolved = await response.resolve();

        if (resolved.success) {
          const newIconIdentifier = resolved.hidden ? 'actions-toggle-off' : 'actions-toggle-on';
          const newIconMarkup = await getIcon(newIconIdentifier);

          $button.data('state', resolved.hidden).empty().append(newIconMarkup);
          Notification.success('Success', resolved.message, 1);
        }
      }, function (error) {
        Notification.error('Error', error.message, 1);
      });
    });
  });

  /**
   * Toggle category expands/collapses
   */
  $wrapper.find('details').on('toggle', handleToggle);

  /**
   * Filter categories  
   */
  $filter.on('input', function () {
    const filter = $(this).val().toLowerCase();

    $wrapper.find('li').each(function () {
      const $item = $(this);
      const text = $item.text().toLowerCase();

      if (text.indexOf(filter) !== -1) {
        // On garde l’élément visible
        $item.show();

        // On remonte les parents <li> et on les garde visibles aussi
        $item.parents('li').show();
        // Désactiver temporairement
        $wrapper.find('details').off('toggle', handleToggle);
        $item.parents('li').find('details').attr('open', true);
      } else {
        $item.hide();
      }
      if (filter === '') {
        // Désactiver temporairement
        $wrapper.find('details').off('toggle', handleToggle);
        $expandedDefaults.each(function() {
          const $details = $(this);
          $details.attr('open', true);
        });
      } else {
        // nettoyage
        $wrapper.find('.move-arrow').remove();
        $wrapper.find('li').removeClass('cut-active');
      }
    });
    setTimeout(() => {
      $wrapper.find('details').on('toggle', handleToggle);
    }, 200);

  });

  /**
   * move category to this position
   */
  $wrapper.on('click', '.move-arrow', function () {

    let targetUid = $(this).data('target');
    let cutCategoryUid = $(this).data('category');
    let pid = $(this).data('page');

    $.ajax({
      url: TYPO3.settings.ajaxUrls['w3c_categorymanager_move'],
      method: 'POST',
      data: {
        cutUid: cutCategoryUid,
        targetUid: targetUid,
        pid: pid,
      },
      success: function (response) {
        // Recharger / rerender l’arborescence
        window.location.reload();

      }
    });

    // Nettoyage
    cutCategoryUid = null;
    $wrapper.find('.move-arrow-li').remove();
    $wrapper.find('li').removeClass('cut-active');
  });

  /**
   * select category to move
   */
  $moveButtons.each(function () {
    const $button = $(this);
    const $li = $(this).closest('li');
    const uid = $button.data('uid');
    const params = new URLSearchParams(window.location.search);
    const pid = params.get('id');

    $button.on('click', function(){
      // Si on est déjà en mode déplacement, on annule
      if( $li.hasClass('cut-active') ){
        // nettoyage
        $wrapper.find('.move-arrow-li').remove();
        $wrapper.find('li').removeClass('cut-active');
        $expandedDefaults.each(function() {
          const $details = $(this);
          $details.attr('open', true);
        });
        return;
      }
      // Désactiver temporairement
      $wrapper.find('details').off('toggle', handleToggle);

      $li.parent().find('details').removeAttr('open');
      $li.siblings('li').show();

      // nettoyage
      $wrapper.find('.move-arrow-li').remove();
      $wrapper.find('li').removeClass('cut-active');

      // marquer active
      $li.addClass('cut-active');

      // Au-dessus de la premiere catégorie
      if (!$li.is(':first-child')) {
        getIcon('actions-arrow-right').then(iconMarkup => {
          $li.parent().prepend('<li class="move-arrow-li">'
            + '<a href="#" class="move-arrow" data-page="'+pid+'" data-target="0" data-category="'+uid+'" title="'+$('#w3c_categorymanager_move_message').val()+'">'
            + iconMarkup 
            + '</a></li>');
        });
      }

      $li.siblings('li').each(function () {
        const target = $(this).data('uid');

        // en-dessous
        if( $li.prev().data('uid') != target ){
          getIcon('actions-arrow-right').then(iconMarkup => {
            $(this).after('<li class="move-arrow-li"><a href="#" class="move-arrow" data-page="'
              + pid + '" data-target="'
              + target + '" data-category="' 
              + uid + '" title="'+$('#w3c_categorymanager_move_message').val()+'">'
              + iconMarkup + '</a></li>');
          });
        }
      });
      setTimeout(() => {
        $wrapper.find('details').on('toggle', handleToggle);
        $filter.val('');
      }, 200);
    });
  });

  /**
   * Cancel move on ESC key
   */
  $(document).on('keydown', function(event) {
    if (event.key === "Escape" || event.keyCode === 27) {
      // nettoyage
      $wrapper.find('.move-arrow-li').remove();
      $wrapper.find('li').removeClass('cut-active');
      $expandedDefaults.each(function() {
        const $details = $(this);
        $details.attr('open', true);
      });
    }
  });
});
