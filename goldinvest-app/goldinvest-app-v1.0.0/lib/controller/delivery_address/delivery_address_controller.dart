import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/profile/states_info_model.dart'
    as statesModel;

import '../../backend/model/profile/cities_info_model.dart' as citiesModel;
import '../../backend/model/profile/profile_info_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class DeliveryAddressController extends GetxController {
  RxString selectCountry = ''.obs;
  RxString selectState = ''.obs;
  RxString selectCity = ''.obs;
  RxString phoneCode = ''.obs;
  RxInt countryId = 0.obs;
  RxInt stateId = 0.obs;
  final zipCode = TextEditingController();
  final address = TextEditingController();
  final phoneNumber = TextEditingController();
  List<Country> sendingCountryInfo = [];
  List<statesModel.State> sendingStateInfo = [];
  List<citiesModel.City> sendingCityInfo = [];
  RxString deliveryAddress = ''.obs;

  @override
  void onInit() {
    countryInfoProcess();

    super.onInit();
  }

  @override
  void onClose() {
    zipCode.dispose();
    address.dispose();
    phoneNumber.dispose();
    super.onClose();
  }

  final _isCountryLoading = false.obs;
  bool get isCountryLoading => _isCountryLoading.value;

  //country

  late ProfileInfoModel _profileInfoModel;
  ProfileInfoModel get profileInfoModel => _profileInfoModel;

  Future<ProfileInfoModel?> countryInfoProcess() async {
    return RequestProcess().request<ProfileInfoModel>(
      showResult: true,
      fromJson: ProfileInfoModel.fromJson,
      apiEndpoint: ApiEndpoint.profileInfo,
      isLoading: _isCountryLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _profileInfoModel = value!;

        selectCountry.value = _profileInfoModel.data.countries.first.name;

        for (var countries in _profileInfoModel.data.countries) {
          sendingCountryInfo.add(
            Country(
              currencyCode: countries.currencyCode,
              currencyName: countries.currencyName,
              currencySymbol: countries.currencySymbol,
              id: countries.id,
              mobileCode: countries.mobileCode,
              name: countries.name,
            ),
          );
        }
      },
    );
  }

  final _isStateLoading = false.obs;
  bool get isStateLoading => _isStateLoading.value;

  //state

  late statesModel.StatesInfoModel _statesInfoModel;
  statesModel.StatesInfoModel get statesInfoModel => _statesInfoModel;

  Future<statesModel.StatesInfoModel?> stateInfoProcess() async {
    return RequestProcess().request<statesModel.StatesInfoModel>(
      showResult: true,
      fromJson: statesModel.StatesInfoModel.fromJson,
      apiEndpoint: ApiEndpoint.states,
      isLoading: _isStateLoading,
      method: HttpMethod.GET,
      queryParams: {'id': countryId.value.toString()},
      showSuccessMessage: false,
      onSuccess: (value) {
        _statesInfoModel = value!;

        selectState.value = _statesInfoModel.data.states.first.name;

        for (var states in _statesInfoModel.data.states) {
          sendingStateInfo.add(
            statesModel.State(
                countryId: states.countryId,
                id: states.id,
                name: states.name,
                stateCode: states.stateCode),
          );
        }
      },
    );
  }

  final _isCitiesLoading = false.obs;
  bool get isCitiesLoading => _isCitiesLoading.value;

  //cities

  late citiesModel.CitiesInfoModel _cityInfoModel;
  citiesModel.CitiesInfoModel get cityInfoModel => _cityInfoModel;

  Future<citiesModel.CitiesInfoModel?> cityInfoProcess() async {
    return RequestProcess().request<citiesModel.CitiesInfoModel>(
      showResult: true,
      fromJson: citiesModel.CitiesInfoModel.fromJson,
      apiEndpoint: ApiEndpoint.cities,
      isLoading: _isCitiesLoading,
      method: HttpMethod.GET,
      queryParams: {'id': stateId.value.toString()},
      showSuccessMessage: false,
      onSuccess: (value) {
        _cityInfoModel = value!;

        selectCity.value = _cityInfoModel.data.cities.first.name;

        for (var cities in _cityInfoModel.data.cities) {
          sendingCityInfo.add(
            citiesModel.City(
                id: cities.id,
                name: cities.name,
                stateCode: cities.stateCode,
                stateName: cities.stateName),
          );
        }
      },
    );
  }
}
