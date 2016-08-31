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

    var TeamViewerWidgetExt = function(){
    };
    TeamViewerWidgetExt.ATTRS = {
        teamid: NS.ATTRIBUTE.teamid,
        team: NS.ATTRIBUTE.team,
        teamApp: NS.ATTRIBUTE.teamApp,
    };
    TeamViewerWidgetExt.prototype = {
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

            this.onLoadTeam(team);
        },
        onLoadTeam: function(team){
        },
    };
    NS.TeamViewerWidgetExt = TeamViewerWidgetExt;
};