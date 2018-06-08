<?php
include 'head.php';
?>
<div style='border:1px solid #ccc; padding:0px 35px;'>
<h1>Contract View</h1>
<a href=#>Edit</a>

<div>
  <a href=#>Return</a>
</div>

<div>
  <h2>Parties</h2>
  <ul>
    <li><a href='#'>ABCDEF1234567890</a> <a href=#>Edit</a></li>
    <li><a href='#'>1234567890ABCDEF</a> <a href=#>Edit</a></li>
    <li><a href=#>Add..</a></li>
  </ul>

  <h2>Terms</h2>
  <ul>
    <li>share 100 muffins per month <a href=#>Edit</a></li>
    <li>receive 8 pizzas per week <a href=#>Edit</a></li>
    <li style='white-space:pre'><?=json_encode(array(
      'description' => 'this is an example of a json-encoded machine-readable contractual term',
      'party' => array('ABCDEF1234567890' => '100 muffins/mo',
                       '1234567890ABCDEF' => '8 pizzas/wk'
                 ),
    ),JSON_PRETTY_PRINT)?> <a href=#>Edit</a></li>
    <li><a href=#>Add..</a></li>
  </ul>

  <h2>Metadata</h2>
  <ul>
    <li><b>ID:</b> 9</li>
    <li><b>Hash:</b> <?=md5('1A2B3C4D5E6F7A8B9C0D')?></li>
    <li><b>PrevHash:</b> <?=md5(md5('1A2B3C4D5E6F7A8B9C0D'))?></li>
    <li><b>Timestamp:</b> 2018-03-21</li>
    <li><b>Start Date:</b> 2018-03-21</li>
    <li><b>End Date:</b> 2018-06-21</li>
    <li><b>Nonce:</b> 1A2B3C4D5E6F7A8B9C0D</li>
  </ul>

  <h2>Comments</h2>
  <ul>
    <li><i>N/A</i></li>
  </ul>

  <h2>Disputes</h2>
  <ul>
    <li><i>N/A</i></li>
  </ul>

  <h2>History</h2>
  <ul>
    <li>ABCDEF1234567890 created the contract.</li>
    <li>ABCDEF1234567890 signed the contract.</li>
  </ul>
</div>

</div>
</body>
