import 'dart:convert';

import '../../../widgets/drop_down/custom_dropdown_menu.dart';

CitiesInfoModel citiesInfoModelFromJson(String str) =>
    CitiesInfoModel.fromJson(json.decode(str));

String citiesInfoModelToJson(CitiesInfoModel data) =>
    json.encode(data.toJson());

class CitiesInfoModel {
  Message message;
  Data data;
  String type;

  CitiesInfoModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory CitiesInfoModel.fromJson(Map<String, dynamic> json) =>
      CitiesInfoModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
        type: json["type"],
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
        "type": type,
      };
}

class Data {
  List<City> cities;

  Data({
    required this.cities,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        cities: List<City>.from(json["cities"].map((x) => City.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "cities": List<dynamic>.from(cities.map((x) => x.toJson())),
      };
}

class City implements DropdownMenuModel {
  String name;
  int id;
  String stateCode;
  String stateName;

  City({
    required this.name,
    required this.id,
    required this.stateCode,
    required this.stateName,
  });

  factory City.fromJson(Map<String, dynamic> json) => City(
        name: json["name"],
        id: json["id"],
        stateCode: json["state_code"],
        stateName: json["state_name"],
      );

  Map<String, dynamic> toJson() => {
        "name": name,
        "id": id,
        "state_code": stateCode,
        "state_name": stateName,
      };

  @override
  String get title => name;
}

class Message {
  List<String> success;

  Message({
    required this.success,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
