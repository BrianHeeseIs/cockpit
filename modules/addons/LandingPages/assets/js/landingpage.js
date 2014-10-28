(function($){

    App.module.controller("landingpage", function($scope, $rootScope, $http, $timeout, Contentfields){

        var id = $("[data-ng-controller='landingpage']").data("id");

        if (id) {

            $http.post(App.route("/api/landingpages/findOne"), {filter: {"_id":id}}, {responseType:"json"}).success(function(data){

                if (data && Object.keys(data).length) {
                    $scope.landingpage = data;
                }

            }).error(App.module.callbacks.error.http);

        } else {

            $scope.landingpage = {
                name: "",
                fields: [],
                sortfield: "created",
                sortorder: "-1"
            };
        }

        $scope.landingpages   = [];
        $scope.groups        = [];
        $scope.contentfields = Contentfields.fields();

        // get landingpages
        $http.post(App.route("/api/landingpages/find"), {}).success(function(data){
            $scope.landingpages = data;
        });

        // get groups
        $http.post(App.route("/api/landingpages/getGroups"), {}).success(function(groups){

            $scope.groups = groups;

        }).error(App.module.callbacks.error.http);

        $scope.addfield = function(){

            if (!$scope.landingpage.fields) {
                $scope.landingpage.fields = [];
            }

            $scope.landingpage.fields.push({
                "name": "",
                "type": "text",
                "lst": false,
                "required": false
            });
        };

        $scope.remove = function(field) {

            var index = $scope.landingpage.fields.indexOf(field);

            if (index > -1) {
                $scope.landingpage.fields.splice(index, 1);
            }
        };

        $scope.toggleOptions = function(index) {
            $("#options-field-"+index).toggleClass('uk-hidden');
        };

        $scope.save = function() {

            var landingpage = angular.copy($scope.landingpage);

            $http.post(App.route("/api/landingpages/save"), {"landingpage": landingpage}).success(function(data){

                if (data && Object.keys(data).length) {
                    $scope.landingpage._id = data._id;
                    App.notify(App.i18n.get("LandingPage saved!"), "success");
                }

            }).error(App.module.callbacks.error.http);
        };

        $scope.$watch('landingpage.fields', function() {

            var sortfields = [{name: 'created', label:'created'}, {name: 'modified', label:'modified'}, {name:'custom-order', label:'custom'}];

            if ($scope.landingpage && $scope.landingpage.fields) {
                sortfields = sortfields.concat($scope.landingpage && $scope.landingpage.fields ? angular.copy($scope.landingpage.fields):[]);
            }

            $timeout(function() {
                $scope.sortfields = sortfields;
            });

        }, true);

        // after sorting list
        $(function(){

            var list = $("#fields-list").on("uk.nestable.stop", function(){
                var fields = [];

                list.children('.ng-scope').each(function(){
                    fields.push(angular.copy($(this).scope().field));
                });

                $scope.$apply(function(){
                    $scope.landingpage.fields = fields;
                });
            });

        });


        // bind clobal command + save
        Mousetrap.bindGlobal(['command+s', 'ctrl+s'], function(e) {
            if (e.preventDefault) {
                e.preventDefault();
            } else {
                e.returnValue = false; // ie
            }
            $scope.save();
            return false;
        });

    });

})(jQuery);
