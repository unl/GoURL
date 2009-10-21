<?php
if (isset($_POST, $_POST['urlID'])) {
    $lilurl->deleteURL($_POST['urlID'], $cas_client->getUser());
}
$urls = $lilurl->getUserURLs($cas_client->getUser());
if (mysql_num_rows($urls)) {
    echo '<h3>Here you can manage the urls you\'ve submitted:</h3>';
    echo '<table class="zentable cool">
    <tr><th>Short ID</th><th>Long URL</th><th>Delete</th></tr>';
    while ($row = mysql_fetch_assoc($urls)) { ?>
    <tr>
        <td><?php echo $row['urlID']; ?></td>
        <td><?php echo $row['longURL']; ?></td>
        <td>
        <form action="?manage" method="post">
            <input type="hidden" name="urlID" value="<?php echo $row['urlID']; ?>" />
            <input type="submit" name="submit" value="Delete" onclick="return confirm('Are you for sure?');" />
        </form>
        </td>
    </tr>
<?php 
    }
    echo '</table>';
} else { ?>
    <h3>You have not submitted any urls</h3>
<?php 
}
?>