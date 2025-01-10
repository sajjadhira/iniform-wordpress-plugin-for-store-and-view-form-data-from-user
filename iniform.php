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

    <?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iniform_submit'])) {

    ?>
        <style>
            .success-message {
                color: green;
                font-weight: bold;
                margin-top: 20px;
                text-align: center;
            }
        </style>
        <?php
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
            echo '<p class="success-message">Thank you for your submission!</p>';
        } else {
            echo '<p>Invalid phone number. Please ensure it is 11 digits.</p>';
        }
    } else {
        ?>
        <style>
            .iniform-form {
                max-width: 600px;
                margin: 20px auto;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .iniform-form label {
                font-weight: bold;
                display: block;
                margin-bottom: 5px;
            }

            .iniform-form input,
            .iniform-form select,
            .iniform-form textarea {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }

            .iniform-form input[type="submit"] {
                background-color: #0073aa;
                color: white;
                border: none;
                cursor: pointer;
                font-weight: bold;
            }

            .iniform-form input[type="submit"]:hover {
                background-color: #005f8d;
            }
        </style>
        <form method="post" action="" class="iniform-form">
            <label for="iniform-name">Name:</label>
            <input type="text" id="iniform-name" name="iniform_name" placeholder="Your Name" required>

            <label for="iniform-phone">Phone Number:</label>
            <input type="text" id="iniform-phone" name="iniform_phone" pattern="\d{11}" placeholder="Your 11 Digit Phone Number" required>

            <label for="iniform-interest">Interest:</label>
            <select id="iniform-interest" name="iniform_interest" required>
                <option value="">Select Interest</option>
                <option value="Business Kickstart">Business Kickstart</option>
                <option value="Business Marketing">Business Marketing</option>
                <option value="Business Creation">Business Creation</option>
                <option value="Other">Other</option>
            </select>

            <label for="iniform-description">Description:</label>
            <textarea id="iniform-description" name="iniform_description" placeholder="Type your message...." required></textarea>

            <input type="submit" name="iniform_submit" value="Submit">
        </form>
    <?php
    }

    return ob_get_clean();
}
add_shortcode('iniform_form', 'iniform_form_shortcode');

// Admin menu to display submissions
function iniform_admin_menu()
{
    add_menu_page(
        'IniForm',
        'IniForm',
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

    if (isset($_GET['delete'])) {
        $delete_id = absint($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $delete_id], ['%d']);
        echo '<div class="updated"><p>Submission deleted.</p></div>';
    }

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
    echo '<h1>IniForm</h1>';
    ?>
    <style>
        .iniform-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 16px;
            text-align: left;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .iniform-table th,
        .iniform-table td {
            padding: 12px;
            border: 1px solid #ddd;
        }

        .iniform-table th {
            background-color: #0073aa;
            color: white;
        }

        .iniform-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .iniform-table tr:hover {
            background-color: #f1f1f1;
        }

        .iniform-pagination {
            margin: 20px 0;
            text-align: center;
        }

        .iniform-pagination a {
            margin: 0 5px;
            padding: 5px 10px;
            text-decoration: none;
            color: #0073aa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .iniform-pagination a:hover {
            background-color: #0073aa;
            color: white;
        }

        .no-data {
            font-size: 18px;
            color: #ff0000;
            text-align: center;
            margin-top: 20px;
        }
    </style>
<?php

    if ($submissions) {
        echo '<table class="iniform-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>Interest</th>
                    <th>Date Submitted</th>
                    <th>Actions</th>
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
                <td><a href="' . admin_url('admin.php?page=iniform-submissions&delete=' . $submission->id) . '" onclick="return confirm(\'Are you sure you want to delete this submission?\')">Delete</a></td>
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

        echo '<div class="iniform-pagination">';
        echo paginate_links($pagination_args);
        echo '</div>';
    } else {
        echo '<p class="no-data">No submissions found.</p>';
    }

    echo '</div>';
}
