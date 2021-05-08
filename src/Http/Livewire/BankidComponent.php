<?php
namespace Patrikgrinsvall\LaravelBankid\Http\Livewire;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Livewire\Component;
use Patrikgrinsvall\LaravelBankid\Bankid;

class BankidComponent extends Component
{
    private $bankid;                                                                        // external dependency.
    const DEFAULT_PERSONALNUMBER = "193204101488";                                       // default value for personal number
    protected $rules = [ 'personalNumber' => 'required|min:12' ];            // validation rules
    public $message = "Enter personal number";                              // default message
    public $status = "WAITING";
    public $personalNumber = self::DEFAULT_PERSONALNUMBER;                         // inputbox prewritten personalNumber
    protected $listeners = [ 'personalNumberClick' => 'personalNumberClick' ];   // event for clicking input
    public $orderRef = "";
    public $hintCode = "";

    /**
     * Initialize bankid here so we crash early if something missconfigured. (maybe not good for unit test @todo refactor)
     *
     * @param UUID $id
     */
    public function __construct($id = null)
    {
        App::setLocale("sv");
        $this->bankid = new Bankid();
        $this->bankid->check_configuration();
        $this->message = __('bankid.RFA1');
        parent::__construct($id = null);
    }

    /**
     * Event when personalNumberfield is clicked
     *
     * @return void
     */
    public function personalNumberClick()
    {
        if ($this->personalNumber === self::DEFAULT_PERSONALNUMBER) {
            $this->personalNumber = "";
        }
    }

    /**
     * Collect result for sign or auth
     *
     * @return void
     */
    public function collect()
    {

        if (in_array($this->status, ['collect', 'pending', 'outstandingTransaction']) &&
            in_array($this->status, ['failed', 'error', 'alreadyInProgress']) === false)
        {
            $result = $this->bankid->collect(['orderRef' => $this->orderRef]);

            $this->updateState($result);
            if ($result['status'] == 'complete') {
                $this->message .= "<script>setTimeout(function(){window.location='/bankid/complete'},2000);</script>";
            }
        } else {
            Log::error("Unknown status: ". $this->status);
            $this->status = 'failed';
            unset($this->orderRef);
        }
    }

    /**
     * Update internal state with bankid response
     *
     * @param [type] $result
     * @return void
     */
    private function updateState(array $result)
    {
        foreach ($result as $key => $val) {
            $key = trim($key);
            $this->$key = trim($val);
            Log::error("setting $key to $val");
        }
        $this->message = __('bankid.RFA1');
    }

    /**
     * Start an auth
     *
     * @return void
     */
    public function authenticate()
    {
        $result = $this->bankid->Authenticate($this->personalNumber);
        $this->updateState($result);
    }

    /**
     * Display view
     *
     * @return void
     */
    public function render()
    {
        return view('livewire.bankid-personalnumber'); // this is taken from project now, this should be published to resource folder
    }
}
