<?php
// helper functions for listings
function listing_status_ru($s){
  switch($s){
    case 'PENDING': return 'На модерации';
    case 'ACTIVE': return 'Активное';
    case 'REJECTED': return 'Отклонено';
    case 'ARCHIVED': return 'Архив';
    default: return $s;
  }
}
?>