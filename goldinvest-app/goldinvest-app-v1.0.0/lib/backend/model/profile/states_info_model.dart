import 'dart:convert';

import '../../../widgets/drop_down/custom_dropdown_menu.dart';

StatesInfoModel statesInfoModelFromJson(String str) =>
    StatesInfoModel.fromJson(json.decode(str));

String statesInfoModelToJson(StatesInfoModel data) =>
    json.encode(data.toJson());

class StatesInfoModel {
  Message message;
  Data data;
  String type;

  StatesInfoModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory StatesInfoModel.fromJson(Map<String, dynamic> json) =>
      StatesInfoModel(
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
  List<State> states;

  Data({
    required this.states,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        states: List<State>.from(json["states"].map((x) => State.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "states": List<dynamic>.from(states.map((x) => x.toJson())),
      };
}

class State implements DropdownMenuModel {
  int countryId;
  String name;
  int id;
  String stateCode;

  State({
    required this.countryId,
    required this.name,
    required this.id,
    required this.stateCode,
  });

  factory State.fromJson(Map<String, dynamic> json) => State(
        countryId: json["country_id"],
        name: json["name"],
        id: json["id"],
        stateCode: json["state_code"],
      );

  Map<String, dynamic> toJson() => {
        "country_id": countryId,
        "name": name,
        "id": id,
        "state_code": stateCode,
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
