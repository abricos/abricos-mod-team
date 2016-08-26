var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var TeamRowWidgetExt = function(){
    };
    TeamRowWidgetExt.ATTRS = {
        team: {value: null}
    };
    TeamRowWidgetExt.prototype = {
        buildTData: function(){
            return this.get('team').toJSON(true);
        },
        onInitAppWidget: function(err, appInstance, options){
            var tp = this.template;

            this.appURLUpdate();
        },
    };
    NS.TeamRowWidgetExt = TeamRowWidgetExt;

    NS.TeamRowWidget = Y.Base.create('TeamRowWidget', SYS.AppWidget, [
        NS.TeamRowWidgetExt
    ], {
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item'},
        },
        CLICKS: {}
    });

    NS.TeamListWidget = Y.Base.create('teamListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this._wsList = [];
            this.reloadTeamList();
        },
        destructor: function(){
            this._cleanTeamList();
        },
        reloadTeamList: function(){
            this.set('waiting', true);

            var appInstance = this.get('appInstance'),
                filter = {
                    ownerModule: this.get('ownerModule')
                };

            appInstance.teamList(filter, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('teamList', result.teamList);

                    var modules = result.teamList.toArray('module', {distinct: true});
                    NS.uses(modules, 'teamList', function(){
                        this.renderTeamList();
                    }, this);
                }
            }, this);
        },
        _cleanTeamList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },
        renderTeamList: function(){
            var teamList = this.get('teamList');
            if (!teamList){
                return;
            }

            var tp = this.template,
                wsList = this._cleanTeamList();

            teamList.each(function(team){
                var ownerModule = team.get('module'),
                    TeamRowWidget = Brick.mod[ownerModule].TeamRowWidget || NS.TeamRowWidget;

                var w = new TeamRowWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    team: team
                });
                wsList[wsList.length] = w;
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,itemWrap'},
            teamList: {value: null},
            ownerModule: {value: ''}
        },
        CLICKS: {}
    });

};