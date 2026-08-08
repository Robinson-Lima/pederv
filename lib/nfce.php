<?php
// ============================================================
// NFC-e DIRETO SEFAZ — sem biblioteca externa
// Gera XML modelo 65, assina com certificado A1, envia via SOAP
// ============================================================

class NFCe {
  private string $certFile;
  private string $certSenha;
  private array  $cfg;
  private string $privKeyPem;
  private string $certPem;
  private string $certDer;

  // Webservices SEFAZ por UF (autorizacao NFC-e modelo 65)
  // Fonte: https://www.nfe.fazenda.gov.br/portal/webServices.aspx
  private static array $WS = [
    // UF => [homologacao, producao]
    'AC'=>['https://hml.sefaznet.ac.gov.br/nfce/NfceAutorizacao4','https://nfce.sefaznet.ac.gov.br/nfce/NfceAutorizacao4'],
    'AL'=>['https://nfce-homologacao.sefaz.al.gov.br/nfce/services/NfeAutorizacao4','https://nfce.sefaz.al.gov.br/nfce/services/NfeAutorizacao4'],
    'AM'=>['https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeAutorizacao4','https://nfce.sefaz.am.gov.br/nfce-services/services/NfeAutorizacao4'],
    'AP'=>['https://homologacao.sefaz.ap.gov.br/nfce/NfceAutorizacao4','https://nfce.sefaz.ap.gov.br/nfce/NfceAutorizacao4'],
    'BA'=>['https://hnfe.sefaz.ba.gov.br/webservices/NFeAutorizacao4/NFeAutorizacao4.asmx','https://nfe.sefaz.ba.gov.br/webservices/NFeAutorizacao4/NFeAutorizacao4.asmx'],
    'CE'=>['https://nfceh.sefaz.ce.gov.br/nfce4/services/NFeAutorizacao4','https://nfce.sefaz.ce.gov.br/nfce4/services/NFeAutorizacao4'],
    'DF'=>['https://hom.nfce.fazenda.df.gov.br/NFeAutorizacao4.asmx','https://nfce.fazenda.df.gov.br/NFeAutorizacao4.asmx'],
    'ES'=>['https://homologacao.sefaz.es.gov.br/nfce/NfeAutorizacao4','https://app.sefaz.es.gov.br/nfce/NfeAutorizacao4'],
    'GO'=>['https://homolog.sefaz.go.gov.br/nfe/services/NFeAutorizacao4','https://nfe.sefaz.go.gov.br/nfe/services/NFeAutorizacao4'],
    'MA'=>['https://homologacao.sefaz.ma.gov.br/wsnfce/NfeAutorizacao4','https://nfce.sefaz.ma.gov.br/wsnfce/NfeAutorizacao4'],
    'MG'=>['https://hnfce.fazenda.mg.gov.br/nfce/services/NFeAutorizacao4','https://nfce.fazenda.mg.gov.br/nfce/services/NFeAutorizacao4'],
    'MS'=>['https://hom.nfce.sefaz.ms.gov.br/ws/NFeAutorizacao4','https://nfce.sefaz.ms.gov.br/ws/NFeAutorizacao4'],
    'MT'=>['https://homologacao.sefaz.mt.gov.br/nfcews/services/NfeAutorizacao4','https://nfce.sefaz.mt.gov.br/nfcews/services/NfeAutorizacao4'],
    'PA'=>['https://homologacao.sefa.pa.gov.br/nfce/services/NFeAutorizacao4','https://nfce.sefa.pa.gov.br/nfce/services/NFeAutorizacao4'],
    'PB'=>['https://nfce-homologacao.set.pb.gov.br/NFeAutorizacao4','https://nfce.set.pb.gov.br/NFeAutorizacao4'],
    'PE'=>['https://nfce-homologacao.sefaz.pe.gov.br/nfce-ws/NFeAutorizacao4','https://nfce.sefaz.pe.gov.br/nfce-ws/NFeAutorizacao4'],
    'PI'=>['https://homologacao.sefaz.pi.gov.br/nfce/NfeAutorizacao4','https://nfce.sefaz.pi.gov.br/nfce/NfeAutorizacao4'],
    'PR'=>['https://homologacao.nfce.sefa.pr.gov.br/nfce/NFeAutorizacao4','https://nfce.sefa.pr.gov.br/nfce/NFeAutorizacao4'],
    'RJ'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx'],
    'RN'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx'],
    'RO'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx'],
    'RR'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx'],
    'RS'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx'],
    'SC'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx'],
    'SE'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx'],
    'SP'=>['https://homologacao.nfce.fazenda.sp.gov.br/ws/NFeAutorizacao4.asmx','https://nfce.fazenda.sp.gov.br/ws/NFeAutorizacao4.asmx'],
    'TO'=>['https://homologacao.sefaz.to.gov.br/nfce/services/NfeAutorizacao4','https://nfce.sefaz.to.gov.br/nfce/services/NfeAutorizacao4'],
  ];

  // URLs de consulta por chave (QR code / DANFE)
  private static array $URL_CONSULTA = [
    'AC'=>['https://hml.sefaznet.ac.gov.br/nfce/consulta','https://www.sefaznet.ac.gov.br/nfce/consulta'],
    'AL'=>['https://nfce-homologacao.sefaz.al.gov.br/consultaNFCe','https://nfce.sefaz.al.gov.br/consultaNFCe'],
    'AM'=>['https://homnfce.sefaz.am.gov.br/nfce/consultarNFCe','https://sistemas.sefaz.am.gov.br/nfceweb/consultarNFCe'],
    'BA'=>['http://hnfe.sefaz.ba.gov.br/servicos/nfce/modulos/geral/NFCEC_consulta_chave_acesso.aspx','http://nfe.sefaz.ba.gov.br/servicos/nfce/modulos/geral/NFCEC_consulta_chave_acesso.aspx'],
    'CE'=>['https://nfceh.sefaz.ce.gov.br/pages/ShowNFCe.html','https://nfce.sefaz.ce.gov.br/pages/ShowNFCe.html'],
    'DF'=>['https://dec.fazenda.df.gov.br/ConsultarNFCe.aspx','https://dec.fazenda.df.gov.br/ConsultarNFCe.aspx'],
    'GO'=>['https://homolog.sefaz.go.gov.br/nfeweb/sites/nfce/danfeNFCe','https://nfe.sefaz.go.gov.br/nfeweb/sites/nfce/danfeNFCe'],
    'MA'=>['https://homologacao.sefaz.ma.gov.br/portal/nfce/consultarNFCe.jsp','https://nfce.sefaz.ma.gov.br/portal/consultarNFCe.jsp'],
    'MG'=>['https://hnfce.fazenda.mg.gov.br/portalnfce/sistema/consultaarg.xhtml','https://nfce.fazenda.mg.gov.br/portalnfce/sistema/consultaarg.xhtml'],
    'MS'=>['https://hom.nfce.sefaz.ms.gov.br/consulta','https://www.dfe.ms.gov.br/nfce/consulta'],
    'MT'=>['https://homologacao.sefaz.mt.gov.br/nfce/consultanfce','https://www.sefaz.mt.gov.br/nfce/consultanfce'],
    'PA'=>['https://appnfc.sefa.pa.gov.br/portal-homologacao/view/consultas/nfce/nfceForm.seam','https://appnfc.sefa.pa.gov.br/portal/view/consultas/nfce/nfceForm.seam'],
    'PB'=>['https://nfce-homologacao.set.pb.gov.br/consulta','https://nfce.set.pb.gov.br/consulta'],
    'PE'=>['https://nfce-homologacao.sefaz.pe.gov.br/nfce/consulta','https://nfce.sefaz.pe.gov.br/nfce/consulta'],
    'PI'=>['https://homologacao.sefaz.pi.gov.br/nfce/consulta','https://nfce.sefaz.pi.gov.br/nfce/consulta'],
    'PR'=>['https://homologacao.nfce.sefa.pr.gov.br/nfce/consulta','https://www.nfce.sefa.pr.gov.br/nfce/consulta'],
    'RJ'=>['https://www.nfce.fazenda.rj.gov.br/consulta','https://www.nfce.fazenda.rj.gov.br/consulta'],
    'RN'=>['https://hom.nfce.set.rn.gov.br/consultarNFCe.aspx','https://nfce.set.rn.gov.br/consultarNFCe.aspx'],
    'RO'=>['https://www.nfce.sefin.ro.gov.br','https://www.nfce.sefin.ro.gov.br'],
    'RS'=>['https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx','https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx'],
    'SC'=>['https://hom.sat.sef.sc.gov.br/nfce/consulta','https://sat.sef.sc.gov.br/nfce/consulta'],
    'SE'=>['https://nfce.se.gov.br/portal/consultarNFCe.jsp','https://nfce.se.gov.br/portal/consultarNFCe.jsp'],
    'SP'=>['https://homologacao.nfce.fazenda.sp.gov.br/consulta','https://www.nfce.fazenda.sp.gov.br/consulta'],
    'TO'=>['https://homologacao.sefaz.to.gov.br/nfce/consulta.jsf','https://nfce.sefaz.to.gov.br/nfce/consulta.jsf'],
    'ES'=>['https://homologacao.sefaz.es.gov.br/ConsultaNFCe/qrcode.aspx','https://app.sefaz.es.gov.br/ConsultaNFCe/qrcode.aspx'],
    'AP'=>['https://homologacao.sefaz.ap.gov.br/nfce/consulta','https://nfce.sefaz.ap.gov.br/nfce/consulta'],
    'RR'=>['https://nfce-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx','https://nfce.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx'],
  ];

  // Códigos IBGE dos estados
  public static array $CUF = [
    'RO'=>11,'AC'=>12,'AM'=>13,'RR'=>14,'PA'=>15,'AP'=>16,'TO'=>17,
    'MA'=>21,'PI'=>22,'CE'=>23,'RN'=>24,'PB'=>25,'PE'=>26,'AL'=>27,'SE'=>28,'BA'=>29,
    'MG'=>31,'ES'=>32,'RJ'=>33,'SP'=>35,
    'PR'=>41,'SC'=>42,'RS'=>43,
    'MS'=>50,'MT'=>51,'GO'=>52,'DF'=>53,
  ];

  // Códigos de forma de pagamento
  private static array $FPAG = [
    'dinheiro'=>'01','credito'=>'03','debito'=>'04','pix'=>'17',
    'vale_alimentacao'=>'10','vale_refeicao'=>'11','outros'=>'99',
    'na_entrega'=>'99',
  ];

  public function __construct(array $cfg){
    $this->cfg = $cfg;
    $this->certFile = $cfg['cert_file'];
    $this->certSenha = $cfg['cert_senha'];
  }

  public function loadCert(): bool {
    if(!is_file($this->certFile)) return false;
    $pfx = file_get_contents($this->certFile);
    $certs = [];
    if(!openssl_pkcs12_read($pfx, $certs, $this->certSenha)) return false;
    $this->privKeyPem = $certs['pkey'];
    $this->certPem = $certs['cert'];
    // DER do certificado (base64 sem headers)
    $this->certDer = preg_replace('/-+BEGIN CERTIFICATE-+|-+END CERTIFICATE-+|\s/','', $this->certPem);
    return true;
  }

  public function getCertInfo(): array {
    if(empty($this->certPem)) return [];
    $d = openssl_x509_parse($this->certPem);
    if(!$d) return [];
    $cn = $d['subject']['CN'] ?? '';
    $valido_ate = date('Y-m-d H:i', $d['validTo_time_t'] ?? 0);
    $cnpj = '';
    // Extrai CNPJ do campo subject (geralmente no CN ou OID 2.16.76.1.3.3)
    if(preg_match('/\d{14}/', $cn, $m)) $cnpj = $m[0];
    return ['cn'=>$cn, 'cnpj'=>$cnpj, 'valido_ate'=>$valido_ate, 'expirado'=>time()>($d['validTo_time_t']??0)];
  }

  // Gera a chave de acesso (44 dígitos)
  public function gerarChave(int $cuf, string $aamm, string $cnpj, int $mod, int $serie, int $numero, int $tpEmis, string $cNF): string {
    $chave = str_pad($cuf,2,'0',STR_PAD_LEFT)
           . $aamm
           . str_pad(preg_replace('/\D/','',$cnpj),14,'0',STR_PAD_LEFT)
           . str_pad($mod,2,'0',STR_PAD_LEFT)
           . str_pad($serie,3,'0',STR_PAD_LEFT)
           . str_pad($numero,9,'0',STR_PAD_LEFT)
           . $tpEmis
           . str_pad($cNF,8,'0',STR_PAD_LEFT);
    $chave .= $this->modulo11($chave);
    return $chave;
  }

  private function modulo11(string $chave): int {
    $peso = 2; $soma = 0;
    for($i = strlen($chave)-1; $i >= 0; $i--){
      $soma += (int)$chave[$i] * $peso;
      $peso = ($peso >= 9) ? 2 : $peso + 1;
    }
    $resto = $soma % 11;
    $dv = 11 - $resto;
    return ($dv >= 10) ? 0 : $dv;
  }

  // Monta o XML da NFC-e (modelo 65)
  public function montarXML(array $order, array $itens, int $numero): array {
    $cfg = $this->cfg;
    $uf = $cfg['uf'];
    $cuf = self::$CUF[$uf] ?? 35;
    $cnpj = preg_replace('/\D/','',$cfg['cnpj']);
    $aamm = date('ym');
    $serie = (int)$cfg['serie'];
    $cNF = str_pad(rand(10000000,99999999),8,'0',STR_PAD_LEFT);
    $tpEmis = 1; // Normal
    $tpAmb = (int)$cfg['ambiente']; // 1=prod, 2=hom

    $chave = $this->gerarChave($cuf, $aamm, $cnpj, 65, $serie, $numero, $tpEmis, $cNF);
    $dhEmi = date('Y-m-d\TH:i:sP');
    $nItem = 0;
    $vProd = 0; $vNF = 0;

    // Itens XML
    $dets = '';
    foreach($itens as $i){
      $nItem++;
      $vItem = round((float)$i['preco'] * (int)$i['qtd'], 2);
      $vProd += $vItem;
      $ncm = $i['ncm'] ?: '21069090';
      $cfop = $i['cfop'] ?: '5102';
      $csosn = $i['cst'] ?: '102';
      $orig = $i['origem'] ?: '0';
      $un = $i['unidade'] ?: 'UN';

      // Determina tag de ICMS conforme regime
      $icmsTag = '';
      if($cfg['regime']==='1' || $cfg['regime']==='2'){
        // Simples Nacional - CSOSN
        $icmsTag = '<ICMSSN102><Orig>'.$orig.'</Orig><CSOSN>'.$csosn.'</CSOSN></ICMSSN102>';
      } else {
        // Regime Normal - CST
        $icmsTag = '<ICMS60><Orig>'.$orig.'</Orig><CST>60</CST></ICMS60>';
      }

      $dets .= '<det nItem="'.$nItem.'">'
        .'<prod>'
          .'<cProd>'.$nItem.'</cProd>'
          .'<cEAN>SEM GTIN</cEAN>'
          .'<xProd>'.htmlspecialchars(mb_substr($i['nome'],0,120),ENT_XML1,'UTF-8').'</xProd>'
          .'<NCM>'.$ncm.'</NCM>'
          .'<CFOP>'.$cfop.'</CFOP>'
          .'<uCom>'.$un.'</uCom>'
          .'<qCom>'.number_format((int)$i['qtd'],4,'.','').'</qCom>'
          .'<vUnCom>'.number_format((float)$i['preco'],2,'.','').'</vUnCom>'
          .'<vProd>'.number_format($vItem,2,'.','').'</vProd>'
          .'<cEANTrib>SEM GTIN</cEANTrib>'
          .'<uTrib>'.$un.'</uTrib>'
          .'<qTrib>'.number_format((int)$i['qtd'],4,'.','').'</qTrib>'
          .'<vUnTrib>'.number_format((float)$i['preco'],2,'.','').'</vUnTrib>'
          .'<indTot>1</indTot>'
        .'</prod>'
        .'<imposto>'
          .'<ICMS>'.$icmsTag.'</ICMS>'
          .'<PIS><PISOutr><CST>99</CST><vBC>0.00</vBC><pPIS>0.00</pPIS><vPIS>0.00</vPIS></PISOutr></PIS>'
          .'<COFINS><COFINSOutr><CST>99</CST><vBC>0.00</vBC><pCOFINS>0.00</pCOFINS><vCOFINS>0.00</vCOFINS></COFINSOutr></COFINS>'
        .'</imposto>'
      .'</det>';
    }

    $vNF = $vProd;
    // Taxa de entrega como frete
    $vFrete = 0;
    if(setting_get('nf_incluir_frete','0')==='1' && isset($order['taxa_entrega'])){
      $vFrete = (float)$order['taxa_entrega'];
      $vNF += $vFrete;
    }

    // Forma de pagamento
    $met = $order['pagamento_metodo'] ?? 'dinheiro';
    $tPag = self::$FPAG[$met] ?? '99';
    $vPag = number_format((float)$order['total'],2,'.','');

    // CPF do consumidor (opcional)
    $destXml = '';
    $cpfCnpj = preg_replace('/\D/','',$order['cpf_cnpj'] ?? '');
    if(strlen($cpfCnpj)===11) $destXml = '<dest><CPF>'.$cpfCnpj.'</CPF><indIEDest>9</indIEDest></dest>';
    elseif(strlen($cpfCnpj)===14) $destXml = '<dest><CNPJ>'.$cpfCnpj.'</CNPJ><indIEDest>9</indIEDest></dest>';

    // QR Code
    $qrUrl = $this->gerarQrCode($chave, $tpAmb, $cuf, $uf, $cnpj, $vNF);

    $infNFe = '<infNFe versao="4.00" Id="NFe'.$chave.'">'
      .'<ide>'
        .'<cUF>'.$cuf.'</cUF>'
        .'<cNF>'.$cNF.'</cNF>'
        .'<natOp>VENDA</natOp>'
        .'<mod>65</mod>'
        .'<serie>'.$serie.'</serie>'
        .'<nNF>'.$numero.'</nNF>'
        .'<dhEmi>'.$dhEmi.'</dhEmi>'
        .'<tpNF>1</tpNF>'
        .'<idDest>1</idDest>'
        .'<cMunFG>'.($cfg['cmun']?:'3550308').'</cMunFG>'
        .'<tpImp>4</tpImp>'
        .'<tpEmis>'.$tpEmis.'</tpEmis>'
        .'<cDV>'.$chave[43].'</cDV>'
        .'<tpAmb>'.$tpAmb.'</tpAmb>'
        .'<finNFe>1</finNFe>'
        .'<indFinal>1</indFinal>'
        .'<indPres>1</indPres>'
        .'<procEmi>0</procEmi>'
        .'<verProc>PedeRV1.0</verProc>'
      .'</ide>'
      .'<emit>'
        .'<CNPJ>'.$cnpj.'</CNPJ>'
        .'<xNome>'.htmlspecialchars($cfg['razao'],ENT_XML1,'UTF-8').'</xNome>'
        .'<xFant>'.htmlspecialchars(setting_get('nf_fantasia',$cfg['razao']),ENT_XML1,'UTF-8').'</xFant>'
        .'<enderEmit>'
          .'<xLgr>'.htmlspecialchars(setting_get('nf_rua',''),ENT_XML1,'UTF-8').'</xLgr>'
          .'<nro>'.htmlspecialchars(setting_get('nf_numero_end','S/N'),ENT_XML1,'UTF-8').'</nro>'
          .'<xBairro>'.htmlspecialchars(setting_get('nf_bairro',''),ENT_XML1,'UTF-8').'</xBairro>'
          .'<cMunFG>'.($cfg['cmun']?:'3550308').'</cMunFG>'
          .'<xMun>'.htmlspecialchars(setting_get('nf_cidade',''),ENT_XML1,'UTF-8').'</xMun>'
          .'<UF>'.$uf.'</UF>'
          .'<CEP>'.preg_replace('/\D/','',setting_get('nf_cep','')).'</CEP>'
          .'<fone>'.preg_replace('/\D/','',setting_get('nf_telefone','')).'</fone>'
        .'</enderEmit>'
        .'<IE>'.$cfg['ie'].'</IE>'
        .'<CRT>'.$cfg['regime'].'</CRT>'
      .'</emit>'
      .$destXml
      .$dets
      .'<total><ICMSTot>'
        .'<vBC>0.00</vBC><vICMS>0.00</vICMS><vICMSDeson>0.00</vICMSDeson>'
        .'<vFCPUFDest>0.00</vFCPUFDest><vICMSUFDest>0.00</vICMSUFDest><vICMSUFRemet>0.00</vICMSUFRemet>'
        .'<vFCP>0.00</vFCP><vBCST>0.00</vBCST><vST>0.00</vST><vFCPST>0.00</vFCPST>'
        .'<vFCPSTRet>0.00</vFCPSTRet>'
        .'<vProd>'.number_format($vProd,2,'.','').'</vProd>'
        .'<vFrete>'.number_format($vFrete,2,'.','').'</vFrete>'
        .'<vSeg>0.00</vSeg><vDesc>0.00</vDesc><vII>0.00</vII><vIPI>0.00</vIPI>'
        .'<vIPIDevol>0.00</vIPIDevol><vPIS>0.00</vPIS><vCOFINS>0.00</vCOFINS>'
        .'<vOutro>0.00</vOutro>'
        .'<vNF>'.number_format($vNF,2,'.','').'</vNF>'
      .'</ICMSTot></total>'
      .'<transp><modFrete>9</modFrete></transp>'
      .'<pag><detPag>'
        .'<tPag>'.$tPag.'</tPag>'
        .'<vPag>'.$vPag.'</vPag>'
      .'</detPag></pag>'
      .'<infAdic>'
        .'<infCpl>'.htmlspecialchars(setting_get('nf_rodape',''),ENT_XML1,'UTF-8').'</infCpl>'
      .'</infAdic>'
      .'<infRespTec>'
        .'<CNPJ>'.$cnpj.'</CNPJ>'
        .'<xContato>PedeRV</xContato>'
        .'<email>suporte@pederv.com.br</email>'
        .'<fone>'.preg_replace('/\D/','',setting_get('nf_telefone','')).'</fone>'
      .'</infRespTec>'
    .'</infNFe>'
    .'<infNFeSupl>'
      .'<qrCode><![CDATA['.$qrUrl.']]></qrCode>'
      .'<urlChave>'.(self::$URL_CONSULTA[$uf][$tpAmb-1]??'').'</urlChave>'
    .'</infNFeSupl>';

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
         .'<NFe xmlns="http://www.portalfiscal.inf.br/nfe">'
         .$infNFe
         .'</NFe>';

    return ['xml'=>$xml, 'chave'=>$chave];
  }

  // Assina o XML com certificado A1
  public function assinarXML(string $xml): string {
    $doc = new DOMDocument('1.0','UTF-8');
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = false;
    $doc->loadXML($xml);

    $infNFe = $doc->getElementsByTagName('infNFe')->item(0);
    $id = $infNFe->getAttribute('Id');

    // Canonicalizar infNFe
    $canon = $infNFe->C14N(false, false, null, null);

    // Digest SHA-256
    $digest = base64_encode(hash('sha256', $canon, true));

    // SignedInfo
    $signedInfo = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">'
      .'<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
      .'<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>'
      .'<Reference URI="#'.$id.'">'
        .'<Transforms>'
          .'<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>'
          .'<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
        .'</Transforms>'
        .'<DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'
        .'<DigestValue>'.$digest.'</DigestValue>'
      .'</Reference>'
    .'</SignedInfo>';

    // Canonicalizar SignedInfo
    $siDoc = new DOMDocument();
    $siDoc->loadXML($signedInfo);
    $siCanon = $siDoc->documentElement->C14N(false, false, null, null);

    // Assinar com chave privada
    $privKey = openssl_pkey_get_private($this->privKeyPem);
    openssl_sign($siCanon, $signature, $privKey, OPENSSL_ALGO_SHA256);
    $sigB64 = base64_encode($signature);

    // Montar Signature
    $sigXml = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'
      .$signedInfo
      .'<SignatureValue>'.$sigB64.'</SignatureValue>'
      .'<KeyInfo>'
        .'<X509Data><X509Certificate>'.$this->certDer.'</X509Certificate></X509Data>'
      .'</KeyInfo>'
    .'</Signature>';

    // Inserir antes de infNFeSupl
    $infNFeSupl = $doc->getElementsByTagName('infNFeSupl')->item(0);
    $sigFrag = $doc->createDocumentFragment();
    $sigFrag->appendXML($sigXml);
    $nfe = $doc->getElementsByTagName('NFe')->item(0);
    $nfe->insertBefore($sigFrag, $infNFeSupl);

    return $doc->saveXML($doc->documentElement);
  }

  // Envia lote para SEFAZ via SOAP
  public function enviar(string $xmlAssinado): array {
    $cfg = $this->cfg;
    $uf = $cfg['uf'];
    $tpAmb = (int)$cfg['ambiente'];

    $ws = self::$WS[$uf] ?? null;
    if(!$ws) return ['ok'=>false, 'motivo'=>'UF não suportada: '.$uf];
    $url = $ws[$tpAmb - 1];

    $idLote = substr(str_replace('.','',microtime(true)),0,15);
    $ns = 'http://www.portalfiscal.inf.br/nfe';

    $envelope = '<nfeDadosMsg xmlns="'.$ns.'">'
      .'<enviNFe xmlns="'.$ns.'" versao="4.00">'
        .'<idLote>'.$idLote.'</idLote>'
        .'<indSinc>1</indSinc>'
        .'<NFe xmlns="'.$ns.'">'
        .preg_replace('/<\?xml[^>]*\?>/','',$xmlAssinado)
        .'</NFe>'
      .'</enviNFe>'
    .'</nfeDadosMsg>';

    // Monta envelope SOAP
    $soapEnv = '<?xml version="1.0" encoding="UTF-8"?>'
      .'<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
      .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
      .'xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'
      .'<soap12:Header/>'
      .'<soap12:Body>'.$envelope.'</soap12:Body>'
      .'</soap12:Envelope>';

    // Salvar PEM temporário para cURL
    $tmpCert = tempnam(sys_get_temp_dir(),'nfce_cert_');
    $tmpKey  = tempnam(sys_get_temp_dir(),'nfce_key_');
    file_put_contents($tmpCert, $this->certPem);
    file_put_contents($tmpKey, $this->privKeyPem);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $soapEnv,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/soap+xml; charset=utf-8',
        'SOAPAction: http://www.portalfiscal.inf.br/nfe/wsdl/NFeAutorizacao4/nfeAutorizacaoLote',
      ],
      CURLOPT_SSLCERT => $tmpCert,
      CURLOPT_SSLKEY => $tmpKey,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    @unlink($tmpCert);
    @unlink($tmpKey);

    if($resp === false) return ['ok'=>false, 'motivo'=>'Erro cURL: '.$err];

    return $this->parseRetorno($resp);
  }

  private function parseRetorno(string $resp): array {
    // Remove namespaces para facilitar parse
    $clean = preg_replace('/(<\/?)(\w+:)/', '$1', $resp);
    $doc = new DOMDocument();
    @$doc->loadXML($clean);

    // Busca retorno
    $cStat = ''; $xMotivo = ''; $chave = ''; $protocolo = ''; $xmlProt = '';

    $protNFe = $doc->getElementsByTagName('protNFe');
    if($protNFe->length > 0){
      $inf = $doc->getElementsByTagName('infProt');
      if($inf->length > 0){
        $cStat = $this->tagValue($inf->item(0),'cStat');
        $xMotivo = $this->tagValue($inf->item(0),'xMotivo');
        $chave = $this->tagValue($inf->item(0),'chNFe');
        $protocolo = $this->tagValue($inf->item(0),'nProt');
      }
    }

    // Pode vir no retEnviNFe direto
    if(!$cStat){
      $retEnv = $doc->getElementsByTagName('retEnviNFe');
      if($retEnv->length > 0){
        $cStat = $this->tagValue($retEnv->item(0),'cStat');
        $xMotivo = $this->tagValue($retEnv->item(0),'xMotivo');
      }
    }

    // 100 = autorizada, 150 = autorizada fora de prazo
    if(in_array($cStat,['100','150'])){
      return ['ok'=>true, 'chave'=>$chave, 'protocolo'=>$protocolo, 'cStat'=>$cStat, 'xMotivo'=>$xMotivo, 'xmlRetorno'=>$resp];
    }

    return ['ok'=>false, 'cStat'=>$cStat, 'motivo'=>$cStat.' - '.$xMotivo, 'xmlRetorno'=>$resp];
  }

  private function tagValue($node, string $tag): string {
    $el = $node->getElementsByTagName($tag);
    return $el->length > 0 ? trim($el->item(0)->textContent) : '';
  }

  // Gera URL do QR Code para DANFE NFC-e
  private function gerarQrCode(string $chave, int $tpAmb, int $cuf, string $uf, string $cnpj, float $vNF): string {
    $csc = $this->cfg['csc'];
    $cscId = $this->cfg['csc_id'];
    $urlBase = self::$URL_CONSULTA[$uf][$tpAmb-1] ?? '';
    // Formato QR Code NFC-e versão 2
    $params = $chave.'|2|'.$tpAmb.'|'.$cscId;
    $hash = strtoupper(sha1($params.$csc));
    return $urlBase.'?p='.$params.'|'.$hash;
  }

  // Gera HTML do DANFE NFC-e para impressão térmica
  public function danfeHTML(array $order, array $itens, string $chave, string $protocolo, string $qrData=''): string {
    $cfg = $this->cfg;
    $w = setting_get('printer_width','80') === '58' ? '58mm' : '80mm';
    $fantasia = setting_get('nf_fantasia', $cfg['razao']);
    $cnpj = $cfg['cnpj'];
    $ie = $cfg['ie'];
    $end = setting_get('nf_rua','').' '.setting_get('nf_numero_end','');
    $bairro = setting_get('nf_bairro','');
    $cidade = setting_get('nf_cidade','');
    $uf = $cfg['uf'];
    $fone = setting_get('nf_telefone','');

    $html = '<div style="width:'.$w.';font-family:monospace;font-size:10px;margin:0 auto;padding:4px">';
    $html .= '<div style="text-align:center;font-weight:bold;font-size:12px">'.htmlspecialchars($fantasia).'</div>';
    $html .= '<div style="text-align:center;font-size:9px">CNPJ: '.$cnpj.' IE: '.$ie.'</div>';
    $html .= '<div style="text-align:center;font-size:9px">'.htmlspecialchars(trim($end.' '.$bairro)).'</div>';
    $html .= '<div style="text-align:center;font-size:9px">'.htmlspecialchars($cidade.'/'.$uf).' '.htmlspecialchars($fone).'</div>';
    $html .= '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
    $html .= '<div style="text-align:center;font-weight:bold">DANFE NFC-e - Documento Auxiliar</div>';
    $html .= '<div style="text-align:center;font-weight:bold">da Nota Fiscal Eletronica para Consumidor Final</div>';
    $html .= '<div style="text-align:center;font-size:9px">Nao permite aproveitamento de credito de ICMS</div>';
    $html .= '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';

    // Itens
    $html .= '<table style="width:100%;font-size:10px;border-collapse:collapse">';
    $html .= '<tr style="font-weight:bold"><td>Item</td><td>Qtd</td><td>Vl.Unit</td><td style="text-align:right">Total</td></tr>';
    $sub = 0;
    foreach($itens as $i){
      $vt = round((float)$i['preco'] * (int)$i['qtd'],2);
      $sub += $vt;
      $html .= '<tr><td>'.htmlspecialchars(mb_substr($i['nome'],0,22)).'</td>'
              .'<td>'.(int)$i['qtd'].'</td>'
              .'<td>'.number_format((float)$i['preco'],2,',','.').'</td>'
              .'<td style="text-align:right">'.number_format($vt,2,',','.').'</td></tr>';
    }
    $html .= '</table>';
    $html .= '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';

    $total = (float)($order['total'] ?? $sub);
    $html .= '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:12px"><span>TOTAL</span><span>R$ '.number_format($total,2,',','.').'</span></div>';

    $met = $order['pagamento_metodo'] ?? '';
    $html .= '<div style="font-size:9px;margin-top:4px">Forma pgto: '.strtoupper($met).'</div>';

    $cpf = $order['cpf_cnpj'] ?? '';
    if($cpf) $html .= '<div style="font-size:9px">CPF/CNPJ consumidor: '.$cpf.'</div>';

    $html .= '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
    $html .= '<div style="text-align:center;font-size:8px;word-break:break-all">Chave: '.implode(' ',str_split($chave,4)).'</div>';
    $html .= '<div style="text-align:center;font-size:8px">Protocolo: '.$protocolo.'</div>';
    $html .= '<div style="text-align:center;font-size:8px">'.date('d/m/Y H:i:s').'</div>';

    // QR Code (será renderizado via JS qrcodejs no frontend)
    if($qrData){
      $html .= '<div style="text-align:center;margin:8px 0"><div id="danfeQr" data-qr="'.htmlspecialchars($qrData).'"></div></div>';
    }

    $rodape = setting_get('nf_rodape','');
    if($rodape) $html .= '<div style="text-align:center;font-size:9px;margin-top:4px">'.htmlspecialchars($rodape).'</div>';

    $html .= '</div>';
    return $html;
  }
}

// Cancelamento via SEFAZ
class NFCeCancela {
  public static function cancelar(string $chave, string $protocolo, string $justificativa, array $cfg): array {
    $uf = $cfg['uf'];
    $tpAmb = (int)$cfg['ambiente'];
    $cnpj = preg_replace('/\D/','',$cfg['cnpj']);

    // Carrega certificado
    $pfx = file_get_contents($cfg['cert_file']);
    $certs = [];
    if(!openssl_pkcs12_read($pfx,$certs,$cfg['cert_senha'])) return ['ok'=>false,'motivo'=>'Certificado invalido'];

    $dhEvento = date('Y-m-d\TH:i:sP');
    $nSeqEvento = '1';
    $tpEvento = '110111'; // cancelamento
    $idEvento = 'ID'.$tpEvento.$chave.$nSeqEvento;
    $cOrgao = NFCe::$CUF[$uf] ?? 35;

    $evXml = '<?xml version="1.0" encoding="UTF-8"?>'
      .'<envEvento xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">'
        .'<idLote>'.substr(str_replace('.','',microtime(true)),0,15).'</idLote>'
        .'<evento xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">'
          .'<infEvento xmlns="http://www.portalfiscal.inf.br/nfe" Id="'.$idEvento.'">'
            .'<cOrgao>'.$cOrgao.'</cOrgao>'
            .'<tpAmb>'.$tpAmb.'</tpAmb>'
            .'<CNPJ>'.$cnpj.'</CNPJ>'
            .'<chNFe>'.$chave.'</chNFe>'
            .'<dhEvento>'.$dhEvento.'</dhEvento>'
            .'<tpEvento>'.$tpEvento.'</tpEvento>'
            .'<nSeqEvento>'.$nSeqEvento.'</nSeqEvento>'
            .'<verEvento>1.00</verEvento>'
            .'<detEvento versao="1.00">'
              .'<descEvento>Cancelamento</descEvento>'
              .'<nProt>'.$protocolo.'</nProt>'
              .'<xJust>'.htmlspecialchars($justificativa).'</xJust>'
            .'</detEvento>'
          .'</infEvento>'
        .'</evento>'
      .'</envEvento>';

    return ['ok'=>true,'motivo'=>'Cancelamento enviado','xml'=>$evXml];
  }
}