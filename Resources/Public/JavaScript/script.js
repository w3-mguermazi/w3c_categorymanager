import $ from 'jquery';
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Icons from '@typo3/backend/icons.js';
import Notification from "@typo3/backend/notification.js";

const getIcon = (identifier, size = Icons.sizes.small) => {
  return Icons.getIcon(identifier, size);
};

$(function () {
  const $wrapper = $('.w3c_categorymanager');
  const $buttons = $('.toggle-category');

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
  $wrapper.find('details').on('toggle', function () {
    const $details = $(this);
    const uid = $details.data('uid');
    const state = this.open ? 1 : 0;

    const data = { uid: uid, state: state };
    const request = new AjaxRequest(TYPO3.settings.ajaxUrls['w3c_categorymanager_toggle_expand']);

    let promise = request.post(data);

    promise.then(async function (response) {
      const resolved = await response.resolve();

      if (resolved.success) {
        // NOTHING HERE
      }
    }, function (error) {
      Notification.error('Error', error.message, 0.8);
    });
  });
});
