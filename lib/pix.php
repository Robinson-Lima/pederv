<?php
// ============================================================
// PIX — gera o "copia e cola" (BR Code EMV) para o QR.
// Algoritmo CRC16-CCITT validado (vetor de teste 0x29B1).
// ============================================================
function pix_crc16($data) {
  $crc = 0xFFFF;
  $len = strlen($data);
  for ($i=0;$i<$len;$i++){
    $crc ^= (ord($data[$i]) << 8);
    for ($j=0;$j<8;$j++){
      $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
      $crc &= 0xFFFF;
    }
  }
  return strtoupper(str_pad(dechex($crc),4,'0',STR_PAD_LEFT));
}

function pix_tlv($id,$valor){
  return $id . str_pad(strlen($valor),2,'0',STR_PAD_LEFT) . $valor;
}

// Remove acentos e limita (nome/cidade EMV são ASCII).
function pix_ascii($s,$max){
  $s = preg_replace('/[^A-Za-z0-9 ]/','', iconv('UTF-8','ASCII//TRANSLIT',$s));
  return strtoupper(substr(trim($s),0,$max));
}

function pix_payload($chave,$favorecido,$cidade,$valor,$txid='***'){
  $gui = pix_tlv('00','br.gov.bcb.pix') . pix_tlv('01',$chave);
  $p  = pix_tlv('00','01');
  $p .= pix_tlv('26',$gui);
  $p .= pix_tlv('52','0000');
  $p .= pix_tlv('53','986');
  if ($valor > 0) $p .= pix_tlv('54', number_format($valor,2,'.',''));
  $p .= pix_tlv('58','BR');
  $p .= pix_tlv('59', pix_ascii($favorecido,25));
  $p .= pix_tlv('60', pix_ascii($cidade,15));
  $p .= pix_tlv('62', pix_tlv('05', substr($txid,0,25)));
  $p .= '6304';
  return $p . pix_crc16($p);
}
