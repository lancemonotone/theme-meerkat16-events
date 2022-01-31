<?php
/**
 * Setting up a custom filter
 * with values sourced from
 * taxonomy: event_category
 */
if (class_exists('Tribe__Events__Filterbar__Filter')) {
    class Wms_Custom_Filters extends Tribe__Events__Filterbar__Filter {
        public $type = 'select';

        public function get_admin_form() {
            $title = $this->get_title_field();
            $type  = $this->get_type_field();

            return $title . $type;
        }

        protected function get_type_field() {
            $name  = $this->get_admin_field_name('type');
            $field = sprintf(__('Type: %s %s', 'tribe-events-filter-view'),
                sprintf('<label><input type="radio" name="%s" value="select" %s /> %s</label>',
                    $name,
                    checked($this->type, 'select', false),
                    __('Dropdown', 'tribe-events-filter-view')
                ),
                sprintf('<label><input type="radio" name="%s" value="checkbox" %s /> %s</label>',
                    $name,
                    checked($this->type, 'checkbox', false),
                    __('Checkboxes', 'tribe-events-filter-view')
                )
            );

            return '<div class="tribe_events_active_filter_type_options">' . $field . '</div>';
        }

        protected function get_values() {
            $wms_cats = array();
            $groups   = $this->category_groups();
            foreach ($groups as $slug => $group) {
                $wms_cats[] = array(
                    'name'  => $group['category_group_name'],
                    'value' => $group['group_slug'],
                    'data'  => array(
                        'slug' => $group['group_slug'],
                    ),
                );
            }

            return $wms_cats;
        }

        protected function setup_query_args() {
            $tax_query_array = array('relation' => 'OR');
            if (is_array($this->currentValue)) {
                foreach ($this->currentValue as $group) {
                    $group           = $this->category_groups($group);
                    $tax_query_array = array_merge($tax_query_array, $this->build_tax_query($group));
                }
            } else {
                $group           = $this->category_groups($this->currentValue);
                $tax_query_array = array_merge($tax_query_array, $this->build_tax_query($group));
            }
            /*
             * Query for specified category groups, but exclude announcements.
             *
             *    Note: Announcements are normally excluded by the exclude_cats()
             *    function in lib/class.event.php. That is not working in the
             *    filter bar context.
             */
            $this->queryArgs = array('tax_query' => array(
                'relation' => 'AND',
                $tax_query_array,
                array(
                    'taxonomy' => TribeEvents::TAXONOMY,
                    'terms'    => array('announcement'),
                    'field'    => 'slug',
                    'operator' => 'NOT IN',
                )
            )
            );
        }

        protected function build_tax_query($group) {
            $tax_query_array = array();
            foreach ($group as $key => $value) {
                if ( ! $value || strpos($key, 'event_') !== 0) continue;
                $tax_query_array[] = array(
                    'taxonomy'         => $key,
                    'field'            => 'id',
                    'terms'            => $value,
                    'include_children' => false,
                );
            }

            return $tax_query_array;
        }

        protected function category_groups($group = '') {
            $groups     = array();
            $groups_raw = get_field('aggregated_categories_group', 'option');
            foreach ($groups_raw as $group_array) {
                $slug                      = sanitize_title($group_array['category_group_name']);
                $group_array['group_slug'] = $slug;
                $groups[ $slug ]           = $group_array;
            }

            return $groups[ $group ] ? $groups[ $group ] : $groups;
        }

    }
}
