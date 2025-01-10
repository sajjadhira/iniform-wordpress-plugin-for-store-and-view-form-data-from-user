<?php
/*
Plugin Name: IniForm
Description: Store and display user form submissions.
Version: 1.0
Author: Pluginoo
Author URI: https://pluginoo.com
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Create the database table upon plugin activation
function iniform_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'iniform_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        phone_number VARCHAR(11) NOT NULL,
        interest VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'iniform_create_table');

// Shortcode to display the form
function iniform_form_shortcode()
{
    ob_start();
?>
    <form method="post" action="">
        <label for="iniform-name">Name:</label><br>
        <input type="text" id="iniform-name" name="iniform_name" required><br><br>

        <label for="iniform-phone">Phone Number:</label><br>
        <input type="text" id="iniform-phone" name="iniform_phone" pattern="\d{11}" required><br><br>

        <label for="iniform-interest">Interest:</label><br>
        <select id="iniform-interest" name="iniform_interest" required>
            <option value="Business Kickstart">Business Kickstart</option>
            <option value="Business Marketing">Business Marketing</option>
            <option value="Business Creation">Business Creation</option>
            <option value="Other">Other</option>
        </select><br><br>

        <label for="iniform-description">Description:</label><br>
        <textarea id="iniform-description" name="iniform_description" required></textarea><br><br>

        <input type="submit" name="iniform_submit" value="Submit">
    </form>
<?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iniform_submit'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'iniform_submissions';

        $name = sanitize_text_field($_POST['iniform_name']);
        $phone_number = sanitize_text_field($_POST['iniform_phone']);
        $interest = sanitize_text_field($_POST['iniform_interest']);
        $description = sanitize_textarea_field($_POST['iniform_description']);

        if (preg_match('/^\d{11}$/', $phone_number)) {
            $wpdb->insert(
                $table_name,
                [
                    'name' => $name,
                    'phone_number' => $phone_number,
                    'interest' => $interest,
                    'description' => $description
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ]
            );
            echo '<p>Thank you for your submission!</p>';
        } else {
            echo '<p>Invalid phone number. Please ensure it is 11 digits.</p>';
        }
    }

    return ob_get_clean();
}
add_shortcode('iniform_form', 'iniform_form_shortcode');

// Admin menu to display submissions
function iniform_admin_menu()
{
    add_menu_page(
        'IniForm Submissions',
        'IniForm Submissions',
        'manage_options',
        'iniform-submissions',
        'iniform_display_submissions',
        'dashicons-feedback'
    );
}
add_action('admin_menu', 'iniform_admin_menu');

// Display submissions in admin panel
function iniform_display_submissions()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'iniform_submissions';

    $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $limit);

    $submissions = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, phone_number, interest, created_at FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $limit,
        $offset
    ));

    echo '<div class="wrap">';
    echo '<h1>IniForm Submissions</h1>';

    if ($submissions) {
        echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>Interest</th>
                    <th>Date Submitted</th>
                </tr>
            </thead>
            <tbody>
        ';

        foreach ($submissions as $submission) {
            echo '<tr>
                <td>' . esc_html($submission->id) . '</td>
                <td>' . esc_html($submission->name) . '</td>
                <td><a href="tel:' . esc_html($submission->phone_number) . '">' . esc_html($submission->phone_number) . '</a></td>
                <td>' . esc_html($submission->interest) . '</td>
                <td>' . esc_html($submission->created_at) . '</td>
            </tr>';
        }

        echo '</tbody>
        </table>';

        $pagination_args = [
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'total' => $total_pages,
            'current' => $page
        ];

        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links($pagination_args);
        echo '</div></div>';
    } else {
        echo '<p>No submissions found.</p>';
    }

    echo '</div>';
}
