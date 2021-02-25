/*global Hunt, VuFind */

/* DbSbg: Overriding original check_item_statuses.js in bootstrap theme. We don't
use the item statuses to save Alma API calls (there is a threshold!) */
VuFind.register('itemStatuses', function ItemStatuses() {
  console.log('itemStatuses')
  return false;
});
