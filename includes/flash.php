<?php
function flash($key, $val = null) {
  if ($val === null) {
    if (isset($_SESSION['flash'][$key])) {
      $v = $_SESSION['flash'][$key];
      unset($_SESSION['flash'][$key]);
      return $v;
    }
    return null;
  } else {
    $_SESSION['flash'][$key] = $val;
  }
}
