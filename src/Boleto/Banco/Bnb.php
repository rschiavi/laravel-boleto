<?php
namespace Eduardokum\LaravelBoleto\Boleto\Banco;

use Eduardokum\LaravelBoleto\Boleto\AbstractBoleto;
use Eduardokum\LaravelBoleto\CalculoDV;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Util;

class Bnb extends AbstractBoleto implements BoletoContract
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setLayoutCnab('400');
    }

    /**
     * Local de pagamento
     *
     * @var string
     */
    protected $localPagamento = 'PAGÁVEL EM QUALQUER AGÊNCIA BANCÁRIA ATÉ O VENCIMENTO';

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_BNB;
    /**
     * Variáveis adicionais.
     *
     * @var array
     */
    public $variaveis_adicionais = [
        'carteira_nome' => '',
    ];
    /**
     * Define as carteiras disponíveis para este banco
     *
     * @var array
     */
    protected $carteiras = ['21', '31', '41'];
    /**
     * Espécie do documento, coódigo para remessa
     *
     * @var string
     */
    protected $especiesCodigo = [
        '240' => [

        ],
        '400' => [
            'DM'  => '01', // Duplicata
            'NP'  => '02', // Nota promissoria
            'CH'  => '03', // Cheque
            'CN'  => '04', // Carnê
            'RC'  => '05', // Recibo
            'ARE' => '16', // Apólice ramos elementares
            'AE'  => '17', // Apólice BANCO DO NORDESTE empresarial
            'AR'  => '18', // Apólice BANCO DO NORDESTE residencial
            'O'   => '19', // Outros
        ]
    ];
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
     * Gera o Nosso Número.
     *
     * @return string
     * @throws \Exception
     */
    protected function gerarNossoNumero()
    {
        $numero_boleto = $this->getNumero();
        return Util::numberFormatGeral($numero_boleto, 7) . CalculoDV::bnbNossoNumero($this->getNumero());
    }
    /**
     * Método que retorna o nosso numero usado no boleto. alguns bancos possuem algumas diferenças.
     *
     * @return string
     */
    public function getNossoNumeroBoleto()
    {
        return substr_replace($this->getNossoNumero(), '-', -1, 0);
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

        $campoLivre = Util::numberFormatGeral($this->getAgencia(), 4);
        $campoLivre .= Util::numberFormatGeral($this->getConta(), 7);
        $campoLivre .= $this->getContaDv() ?: CalculoDV::bnbContaCorrente($this->getAgencia(), $this->getConta());
        $campoLivre .= $this->getNossoNumero();
        $campoLivre .= Util::numberFormatGeral($this->getCarteira(), 2);
        $campoLivre .= '000';

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
            'codigoCliente' => null,
            'convenio' => null,
            'agenciaDv' => null,
            'agencia' => substr($campoLivre, 0, 4),
            'contaCorrente' => substr($campoLivre, 4, 7),
            'contaCorrenteDv' => substr($campoLivre, 11, 1),
            'nossoNumero' => substr($campoLivre, 12, 7),
            'nossoNumeroDv' => substr($campoLivre, 19, 1),
            'nossoNumeroFull' => substr($campoLivre, 12, 8),
            'carteira' => substr($campoLivre, 20, 2),
        ];
    }
}
