var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['tabView.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var TeamViewerWidgetExt = function(){
    };
    TeamViewerWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        teamid: NS.ATTRIBUTE.teamid,
        team: NS.ATTRIBUTE.team,
    };
    TeamViewerWidgetExt.prototype = {
        buildTData: function(){
            return {
                id: this.get('teamid')
            };
        },
        onInitAppWidget: function(err, appInstance){
            var teamApp = this.get('teamApp'),
                teamid = this.get('teamid') | 0;

            this.set('waiting', true);
            teamApp.team(teamid, function(err, result){
                var team = err ? null : result.team;
                this._onLoadTeam(team);
            }, this);
        },
        _onLoadTeam: function(team){
            this.set('waiting', false);
            this.set('team', team);

            var tp = this.template;

            if (team){
                tp.setHTML({
                    title: team.get('title')
                });
            }

            if (tp.one('tabViewWidget')){
                this.addWidget('tabView', new SYS.TabViewWidget({
                    srcNode: tp.one('tabViewWidget')
                }));
            }

            this.appTriggerUpdate();

            this.onLoadTeam(team);
        },
        onLoadTeam: function(team){
        },
    };
    NS.TeamViewerWidgetExt = TeamViewerWidgetExt;
};