<?php
namespace Eduardokum\LaravelBoleto\Boleto\Banco;

use Eduardokum\LaravelBoleto\Boleto\AbstractBoleto;
use Eduardokum\LaravelBoleto\CalculoDV;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Util;

class Caixa  extends AbstractBoleto implements BoletoContract
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setCamposObrigatorios('numero', 'agencia', 'carteira', 'codigoCliente');
    }

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_CEF;
    /**
     * Define as carteiras disponíveis para este banco
     *
     * @var array
     */
    protected $carteiras = ['RG', '01'];
    /**
     * Espécie do documento, coódigo para remessa
     *
     * @var string
     */
    protected $especiesCodigo = [
        '240' => [
            'CH'  => '01', // Cheque
            'DM'  => '02', // Duplicata Mercantil
            'DMI' => '03', // Duplicata Mercantil p/ Indicação
            'DS'  => '04', // Duplicata de Serviço
            'DSI' => '05', // Duplicata de Serviço p/ Indicação
            'DR'  => '06', // Duplicata Rural
            'LC'  => '07', // Letra de Câmbio
            'NCC' => '08', // Nota de Crédito Comercial
            'NCE' => '09', // Nota de Crédito a Exportação
            'NCI' => '10', // Nota de Crédito Industrial
            'NCR' => '11', // Nota de Crédito Rural
            'NP'  => '12', // Nota Promissória
            'NPR' => '13', // Nota Promissória Rural
            'TM'  => '14', // Triplicata Mercantil
            'TS'  => '15', // Triplicata de Serviço
            'NS'  => '16', // Nota de Seguro
            'RC'  => '17', // Recibo
            'FAT' => '18', // Fatura
            'ND'  => '19', // Nota de Débito
            'AP'  => '20', // Apólice de Seguro
            'ME'  => '21', // Mensalidade Escolar
            'PC'  => '22', // Parcela de Consórcio
            'NF'  => '23', // Nota Fiscal
            'DD'  => '24', // Documento de Dívida
            'CPR' => '25', // Cédula de Produto Rural,
            'CC'  => '31', // CC Cartão de Crédito,
            'BP'  => '32', // BDP - Boleto de Proposta
            'OU'  => '99', // Outros
        ],
        '400' => [
            'DM'  => '01', // Duplicata Mercantil
            'NP'  => '02', // Nota Promissória
            'DS'  => '03', // Duplicata de Prestação de Serviços
            'CH'  => '04', // Cheque
            'NS'  => '05', // Nota de Seguro
            'LC'  => '06', // Letra de Câmbio
            'DMI' => '07', // Duplicata Mercantil p/ Indicação
            'NCC' => '08', // Nota de Crédito Comercial
            'OU'  => '09', // Outros
            'NCI' => '10', // Nota de Crédito Industrial
            'NCR' => '11', // Nota de Crédito Rural
            'DSI' => '12', // Duplicata de Serviço p/ Indicação
            'NPR' => '13', // Nota Promissória Rural
            'TM'  => '14', // Triplicata Mercantil
            'TS'  => '15', // Triplicata de Serviço
            'DS'  => '16', // Duplicata Rural
            'RC'  => '17', // Recibo
            'FAT' => '18', // Fatura
            'ND'  => '19', // Nota de Débito
            'AP'  => '20', // Apólice de Seguro
            'ME'  => '21', // Mensalidade Escolar
            'PC'  => '22', // Parcela de Consórcio
            'NF'  => '23', // Nota Fiscal
            'DD'  => '24', // Documento de Dívida
            'CPR' => '25', // Cédula de Produto Rural,
            'NCE' => '11', // Nota de Crédito Exportação
            'CC'  => '31', // Cartão de Crédito,
            'BP' => '32', // Boleto de Proposta
        ]
    ];
    /**
     * Codigo do cliente junto ao banco.
     *
     * @var string
     */
    protected $codigoCliente;
    /**
     * Seta o codigo do cliente.
     *
     * @param mixed $codigoCliente
     *
     * @return $this
     */
    public function setCodigoCliente($codigoCliente)
    {
        $this->codigoCliente = $codigoCliente;

        return $this;
    }
    /**
     * Retorna o codigo do cliente.
     *
     * @return string
     */
    public function getCodigoCliente()
    {
        return $this->codigoCliente;
    }
    /**
     * Retorna o codigo do cliente como se fosse a conta
     * ja que a caixa não faz uso da conta para nada.
     *
     * @return string
     */
    public function getConta()
    {
        return $this->getCodigoCliente();
    }

    /**
     * Gera o Nosso Número.
     *
     * @throws \Exception
     * @return string
     */
    protected function gerarNossoNumero()
    {
        $numero_boleto = Util::numberFormatGeral($this->getNumero(), 15);
        $composicao = '1';
        if ($this->getCarteira() == 'SR') {
            $composicao = '2';
        }

        $carteira = $composicao . '4';
        // As 15 próximas posições no nosso número são a critério do beneficiário, utilizando o sequencial
        // Depois, calcula-se o código verificador por módulo 11
        $numero = $carteira . Util::numberFormatGeral($numero_boleto, 15);
        return $numero;
    }
    /**
     * Método que retorna o nosso numero usado no boleto. alguns bancos possuem algumas diferenças.
     *
     * @return string
     */
    public function getNossoNumeroBoleto()
    {
        return $this->getNossoNumero() . '-' . CalculoDV::cefNossoNumero($this->getNossoNumero());
    }

    /**
     * Na CEF deve retornar agência (sem o DV) / código beneficiário (com DV)
     * @return [type] [description]
     */
    public function getAgenciaCodigoBeneficiario()
    {
        return $this->getAgencia() . ' / ' .
               $this->getCodigoCliente() . '-' .
               Util::modulo11($this->getCodigoCliente());
    }

    /**
     * Seta dias para baixa automática
     *
     * @param int $baixaAutomatica
     *
     * @return $this
     * @throws \Exception
     */
    public function setDiasBaixaAutomatica($baixaAutomatica)
    {
        if ($this->getDiasProtesto() > 0) {
            throw new \Exception('Você deve usar dias de protesto ou dias de baixa, nunca os 2');
        }
        $baixaAutomatica = (int) $baixaAutomatica;
        $this->diasBaixaAutomatica = $baixaAutomatica > 0 ? $baixaAutomatica : 0;
        return $this;
    }

    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     * @throws \Exception
     */
    protected function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        $nossoNumero = Util::numberFormatGeral($this->gerarNossoNumero(), 17);
        $beneficiario = Util::numberFormatGeral($this->getCodigoCliente(), 6);

        $campoLivre = $beneficiario . Util::modulo11($beneficiario);
        $campoLivre .= substr($nossoNumero, 2, 3);
        $campoLivre .= substr($nossoNumero, 0, 1);
        $campoLivre .= substr($nossoNumero, 5, 3);
        $campoLivre .= substr($nossoNumero, 1, 1);
        $campoLivre .= substr($nossoNumero, 8, 9);
        $campoLivre .= Util::modulo11($campoLivre);
        return $this->campoLivre = $campoLivre;
    }

    /**
     * Método onde qualquer boleto deve extender para gerar o código da posição de 20 a 44
     *
     * @param $campoLivre
     *
     * @return array
     */
    public static function parseCampoLivre($campoLivre) {
        return [
            'convenio' => null,
            'agencia' => null,
            'agenciaDv' => null,
            'contaCorrente' => null,
            'contaCorrenteDv' => null,
            'codigoCliente' => substr($campoLivre, 0, 6),
            'carteira' => substr($campoLivre, 10, 1),
            'nossoNumero' => substr($campoLivre, 7, 3) . substr($campoLivre, 11, 3) . substr($campoLivre, 15, 8),
            'nossoNumeroDv' => substr($campoLivre, 23, 1),
            'nossoNumeroFull' => substr($campoLivre, 7, 3) . substr($campoLivre, 11, 3) . substr($campoLivre, 15, 8),
        ];
    }
}
