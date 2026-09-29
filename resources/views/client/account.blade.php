@extends('layouts.original-client')
@section('title', 'Manage Account')
@section('design-page', '6a9adc2c461c43e4b18d6e95')
@section('content')
<main class="main">
        <div class="heading-wrapper">
          <h1>Manage Account</h1>
          <div class="dashboard_info-line">
            <p>Manage your contact information and login details.</p>
          </div>
        </div>
        @if(session('status'))<p class="text-color-green" role="status">{{ session('status') }}</p>@endif
<div class="dashboard-row is-3-col">
          <div class="dashboard_tranding-block">
            <div class="dashboard_block is-dark">
              <div no-scrollbar="" class="dashboard_block-header">
                <a href="#" class="tab-link is-active w-inline-block">
                  <p>General Information</p>
                  <div class="tab-link_line"></div>
                </a>
              </div>
              <div class="account_info">
                <div class="account_info-header">
                  <div class="icon w-embed"><svg width="34" viewbox="0 0 34 34" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" clip-rule="evenodd" d="M17 34C27.88 34 34 27.88 34 17C34 6.12 27.88 0 17 0C6.12 0 0 6.12 0 17C0 27.88 6.12 34 17 34ZM25.8317 27.9985C25.2134 26.8464 24.371 25.8181 23.3439 24.9788C21.5536 23.5154 19.3124 22.7161 17.0002 22.7161C14.688 22.7161 12.4468 23.5154 10.6565 24.9788C9.6295 25.8181 8.78708 26.8464 8.16857 27.9985C10.1901 29.4515 13.0649 30.3571 17.0003 30.3571C20.9355 30.3571 23.8103 29.4515 25.8317 27.9985ZM22.7195 12.7956C22.7195 16.4572 20.6599 18.5168 16.9983 18.5168C13.3368 18.5168 11.2772 16.4572 11.2772 12.7956C11.2772 9.1341 13.3368 7.07448 16.9983 7.07448C20.6599 7.07448 22.7195 9.1341 22.7195 12.7956Z"></path>
                    </svg>
                  </div>
                  <p class="text-size-large text-color-red-orange">{{ $user->name }}</p>
                </div>
                <div class="tags">
                  <p>Company:</p>
                  <p class="tag is-red">{{ $user->company?->name ?? 'SmartSoft' }}</p>
                </div>
                <div class="tags">
                  <p>Role:</p>
                  <p class="tag">{{ $user->is_admin ? 'Administrator' : 'Partner user' }}</p>
                </div>
                <div class="tags">
                  <p>Countries:</p><p class="tag is-grey">Not configured</p>
                  
                  
                  
                  
                </div>
                <div class="tags">
                  <p>Currency:</p>
                  <p class="tag">Not configured</p>
                </div>
              </div>
            </div>
          </div>
          <div class="dashboard_block is-dark">
            <div no-scrollbar="" class="dashboard_block-header">
              <a href="#" class="tab-link is-active w-inline-block">
                <p>Contact information</p>
                <div class="tab-link_line"></div>
              </a>
            </div>
            <div class="w-form">
              <form id="wf-form-Contacts" method="POST" action="{{ route('account.contact') }}">@csrf @method('PATCH')
@foreach($errors->contact->all() as $error)<p class="text-color-red-orange" role="alert">{{ $error }}</p>@endforeach
                <div class="form_fields">
                  <div class="form_field-wrapper"><label for="Email">Email address</label><input class="form_input w-input" maxlength="256" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" data-name="email" value="{{ old('email', $user->email) }}" autocomplete="email" placeholder="" type="email" id="Email" required=""></div>
                  <div class="form_field-wrapper"><label for="Phone">Phone</label><input class="form_input w-input" maxlength="256" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel" data-name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel" placeholder="" type="tel" id="Phone"></div><div class="form_field-wrapper"><label for="wf-form-Contacts-current">Current password</label><input class="form_input w-input" type="password" name="current_password" id="wf-form-Contacts-current" autocomplete="current-password"><p class="text-size-small text-color-subtitles">Required when changing your email address.</p></div><input type="submit" data-wait="Please wait..." class="button is-opposite w-button" value="Save">
                </div>
              </form>
              <div class="w-form-done">
                <div>Thank you! Your submission has been received!</div>
              </div>
              <div class="w-form-fail">
                <div>Oops! Something went wrong while submitting the form.</div>
              </div>
            </div>
          </div>
          <div class="dashboard_block is-dark">
            <div no-scrollbar="" class="dashboard_block-header">
              <a href="#" class="tab-link is-active w-inline-block">
                <p>Log in Details</p>
                <div class="tab-link_line"></div>
              </a>
            </div>
            <div class="w-form">
              <form id="wf-form-Login-details" method="POST" action="{{ route('account.login-details') }}">@csrf @method('PATCH')
@foreach($errors->loginDetails->all() as $error)<p class="text-color-red-orange" role="alert">{{ $error }}</p>@endforeach
                <div class="form_fields">
                  <div class="form_field-wrapper"><label for="User-name">User name</label><input class="form_input w-input" maxlength="256" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" data-name="User name" placeholder="" type="text" id="User-name" required="">
                    <div class="input-icon-wrapper">
                      <div class="icon w-embed"><svg width="22" viewbox="0 0 22 22" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.87399 4.3972C7.75532 4.29896 8.67821 4.2124 9.625 4.2124C10.5718 4.2124 11.4947 4.29896 12.376 4.3972C13.7888 4.55467 14.9236 5.68835 15.0751 7.10402C15.1441 7.74887 15.2062 8.41517 15.2342 9.09647C14.9516 9.02627 14.6609 8.99054 14.3684 8.99054C13.8968 8.99054 13.4298 9.08343 12.994 9.26392C12.5583 9.44441 12.1624 9.70895 11.8289 10.0424C11.4954 10.3759 11.2308 10.7719 11.0504 11.2076C10.9643 11.4153 10.8982 11.6301 10.8525 11.8491C10.5317 11.7599 10.1963 11.7124 9.85342 11.7124C8.35176 11.7124 7.02607 12.6168 6.39627 13.9259C6.52813 13.9655 6.66466 13.9937 6.80467 14.0092C7.81729 14.1213 8.83038 14.2131 9.85408 14.2131C9.91423 14.2131 9.97434 14.2128 10.0344 14.2122C9.97916 14.5601 9.91959 15.0009 9.91824 15.4597C9.82076 15.4615 9.72301 15.4624 9.625 15.4624C8.67821 15.4624 7.75532 15.3758 6.87399 15.2776C5.46123 15.1201 4.32639 13.9864 4.17493 12.5708C4.08111 11.6939 4 10.7774 4 9.8374C4 8.89741 4.08111 7.98089 4.17493 7.10401C4.32639 5.68835 5.46123 4.55467 6.87399 4.3972ZM9.625 10.3682C10.7748 10.3682 11.4216 9.72137 11.4216 8.57153C11.4216 7.42169 10.7748 6.7749 9.625 6.7749C8.47516 6.7749 7.82837 7.42169 7.82837 8.57153C7.82837 9.72137 8.47516 10.3682 9.625 10.3682ZM14.3683 10.2124C14.0608 10.2124 13.7563 10.273 13.4723 10.3906C13.1882 10.5083 12.9301 10.6808 12.7126 10.8982C12.4952 11.1156 12.3227 11.3737 12.2051 11.6578C12.0874 11.9419 12.0269 12.2464 12.0269 12.5538V13.2666C11.6604 13.3785 11.3818 13.6918 11.3185 14.0782L11.305 14.1602C11.2415 14.5448 11.1682 14.9888 11.1682 15.4474C11.1682 15.9061 11.2415 16.3501 11.305 16.7347L11.3185 16.8167C11.394 17.2772 11.7751 17.6338 12.2465 17.6695C12.3545 17.6776 12.4644 17.6863 12.5761 17.6952C13.1406 17.7398 13.7481 17.7879 14.3684 17.7879C14.9886 17.7879 15.5961 17.7398 16.1606 17.6952C16.2722 17.6863 16.3822 17.6776 16.4903 17.6695C16.9617 17.6338 17.3428 17.2772 17.4183 16.8167L17.4318 16.7347C17.4953 16.3501 17.5685 15.9061 17.5685 15.4474C17.5685 14.9888 17.4953 14.5448 17.4318 14.1602L17.4183 14.0782C17.3549 13.6917 17.0763 13.3784 16.7097 13.2666V12.5538C16.7097 12.2464 16.6492 11.9419 16.5315 11.6578C16.4138 11.3737 16.2414 11.1156 16.0239 10.8982C15.8065 10.6808 15.5484 10.5083 15.2643 10.3906C14.9802 10.273 14.6758 10.2124 14.3683 10.2124ZM14.3684 13.107C14.6525 13.107 14.9339 13.1171 15.2097 13.1323V12.5538C15.2097 12.4433 15.188 12.3339 15.1457 12.2318C15.1034 12.1297 15.0414 12.037 14.9633 11.9589C14.8851 11.8807 14.7924 11.8187 14.6903 11.7765C14.5882 11.7342 14.4788 11.7124 14.3683 11.7124C14.2578 11.7124 14.1484 11.7342 14.0463 11.7765C13.9442 11.8187 13.8514 11.8807 13.7733 11.9589C13.6952 12.037 13.6332 12.1297 13.5909 12.2318C13.5486 12.3339 13.5269 12.4433 13.5269 12.5538V13.1323C13.8027 13.1171 14.0842 13.107 14.3684 13.107Z"></path>
                        </svg>
                      </div>
                    </div>
                  </div>
                  <div class="form_field-wrapper"><label for="Set-the-New-Password">Set the New Password</label><input class="form_input w-input" maxlength="256" name="password" autocomplete="new-password" minlength="12" data-name="Set the New Password" placeholder="" type="password" id="Set-the-New-Password">
                    <div class="input-icon-wrapper"><button type="button" aria-label="Show/hide password" class="password-switch">
                        <div class="icon w-embed"><svg width="22" viewbox="0 0 22 22" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.16861 5.22156C3.91372 4.89506 3.97178 4.42374 4.29828 4.16886C4.62479 3.91397 5.0961 3.97202 5.35098 4.29853C6.10059 5.25874 6.78895 6.12061 7.44403 6.91255C8.45885 6.24198 9.6751 5.75027 10.9996 5.75027C12.9017 5.75027 14.5806 6.76443 15.7602 7.86606C16.3547 8.42124 16.8397 9.01413 17.1796 9.54569C17.3495 9.81132 17.4876 10.0685 17.5847 10.3039C17.6781 10.5301 17.7496 10.7735 17.7496 11.0003C17.7496 11.2271 17.6781 11.4705 17.5847 11.6967C17.4876 11.9321 17.3495 12.1892 17.1796 12.4549C16.8397 12.9864 16.3547 13.5793 15.7602 14.1344C15.5819 14.301 15.3921 14.4655 15.1919 14.6249C15.9563 15.2576 16.7863 15.9254 17.7049 16.6516C18.0299 16.9085 18.085 17.3802 17.8281 17.7051C17.5713 18.0301 17.0996 18.0852 16.7747 17.8283C10.9787 13.2463 8.60428 10.9035 4.16861 5.22156ZM12.5829 12.366C12.8559 12.032 12.9996 11.572 12.9996 11.0003C12.9996 9.72027 12.2796 9.00027 10.9996 9.00027C10.4235 9.00027 9.96089 9.1461 9.62626 9.42316C10.5795 10.458 11.5304 11.4037 12.5829 12.366ZM4.81949 9.54569C4.99059 9.27815 5.19842 8.99508 5.43846 8.70909C7.84217 11.6202 9.81427 13.6216 12.6262 16.0067C12.1084 16.161 11.5638 16.2502 10.9996 16.2502C9.09744 16.2502 7.41851 15.2361 6.23888 14.1344C5.64439 13.5793 5.15943 12.9864 4.81949 12.4549C4.64962 12.1892 4.51156 11.9321 4.41438 11.6967C4.321 11.4705 4.24956 11.2271 4.24956 11.0003C4.24956 10.7735 4.321 10.5301 4.41438 10.3039C4.51156 10.0685 4.64962 9.81132 4.81949 9.54569Z"></path>
                          </svg>
                        </div>
                      </button></div>
                  </div>
                  <div class="form_field-wrapper"><label for="Repeat-Password">Repeat Password</label><input class="form_input w-input" maxlength="256" name="password_confirmation" autocomplete="new-password" minlength="12" data-name="Repeat Password" placeholder="" type="password" id="Repeat-Password">
                    <div class="input-icon-wrapper"><button type="button" aria-label="Show/hide password" class="password-switch">
                        <div class="icon w-embed"><svg width="22" viewbox="0 0 22 22" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.16861 5.22156C3.91372 4.89506 3.97178 4.42374 4.29828 4.16886C4.62479 3.91397 5.0961 3.97202 5.35098 4.29853C6.10059 5.25874 6.78895 6.12061 7.44403 6.91255C8.45885 6.24198 9.6751 5.75027 10.9996 5.75027C12.9017 5.75027 14.5806 6.76443 15.7602 7.86606C16.3547 8.42124 16.8397 9.01413 17.1796 9.54569C17.3495 9.81132 17.4876 10.0685 17.5847 10.3039C17.6781 10.5301 17.7496 10.7735 17.7496 11.0003C17.7496 11.2271 17.6781 11.4705 17.5847 11.6967C17.4876 11.9321 17.3495 12.1892 17.1796 12.4549C16.8397 12.9864 16.3547 13.5793 15.7602 14.1344C15.5819 14.301 15.3921 14.4655 15.1919 14.6249C15.9563 15.2576 16.7863 15.9254 17.7049 16.6516C18.0299 16.9085 18.085 17.3802 17.8281 17.7051C17.5713 18.0301 17.0996 18.0852 16.7747 17.8283C10.9787 13.2463 8.60428 10.9035 4.16861 5.22156ZM12.5829 12.366C12.8559 12.032 12.9996 11.572 12.9996 11.0003C12.9996 9.72027 12.2796 9.00027 10.9996 9.00027C10.4235 9.00027 9.96089 9.1461 9.62626 9.42316C10.5795 10.458 11.5304 11.4037 12.5829 12.366ZM4.81949 9.54569C4.99059 9.27815 5.19842 8.99508 5.43846 8.70909C7.84217 11.6202 9.81427 13.6216 12.6262 16.0067C12.1084 16.161 11.5638 16.2502 10.9996 16.2502C9.09744 16.2502 7.41851 15.2361 6.23888 14.1344C5.64439 13.5793 5.15943 12.9864 4.81949 12.4549C4.64962 12.1892 4.51156 11.9321 4.41438 11.6967C4.321 11.4705 4.24956 11.2271 4.24956 11.0003C4.24956 10.7735 4.321 10.5301 4.41438 10.3039C4.51156 10.0685 4.64962 9.81132 4.81949 9.54569Z"></path>
                          </svg>
                        </div>
                      </button></div>
                  </div><div class="form_field-wrapper"><label for="wf-form-Login-details-current">Current password</label><input class="form_input w-input" type="password" name="current_password" id="wf-form-Login-details-current" autocomplete="current-password"><p class="text-size-small text-color-subtitles">Required when changing your password. Leave the new password blank to keep it.</p></div><input type="submit" data-wait="Please wait..." class="button is-opposite w-button" value="Save">
                </div>
              </form>
              <div class="w-form-done">
                <div>Thank you! Your submission has been received!</div>
              </div>
              <div class="w-form-fail">
                <div>Oops! Something went wrong while submitting the form.</div>
              </div>
            </div>
          </div>
        </div>
      </main>
@endsection
